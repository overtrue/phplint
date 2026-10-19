<?php

declare(strict_types=1);

/*
 * This file is part of the overtrue/phplint package
 *
 * (c) overtrue
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\PHPLint\Metadata;

use Composer\InstalledVersions;

use OutOfBoundsException;

use Symfony\Component\Process\Process;

use function dirname;
use function file_exists;
use function json_decode;
use function sprintf;
use function substr;
use function trim;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
final class ApplicationVersion extends Metadata
{
    public const METADATA_ID = 'application_version';

    private const PACKAGE_NAME = 'overtrue/phplint';

    private const UNKNOWN_VERSION = 'UNKNOWN';

    public function __construct(string $description = 'PHPLint Console Application')
    {
        $this->description = $description;

        $installed = InstalledVersions::getAllRawData()[0];

        if (!isset($installed['versions'][self::PACKAGE_NAME])) {
            throw new OutOfBoundsException(sprintf('Package "%s" is not installed', self::PACKAGE_NAME));
        }

        $prettyVersion = $this->getPrettyVersion(
            $installed['versions'][self::PACKAGE_NAME]['pretty_version'] ?? self::UNKNOWN_VERSION
        );

        $version = $installed['versions'][self::PACKAGE_NAME]['version'] ?? 'dev';

        $aliases = $installed['versions'][self::PACKAGE_NAME]['aliases'] ?? [];

        $reference = $installed['versions'][self::PACKAGE_NAME]['reference'];
        if (null === $reference) {
            $reference = $aliases[0] ?? self::UNKNOWN_VERSION;
        }

        $value = [
            'semantic_version' => $version,
            'pretty_version' => $prettyVersion,
            'reference' => $reference,
        ];

        $this->value = json_encode($value, JSON_UNESCAPED_SLASHES);
    }

    public function getLongVersion(): string
    {
        $appName = $this->description;
        $appVersion = json_decode($this->value, true);

        $version = (self::UNKNOWN_VERSION !== $appVersion['pretty_version'])
            ? $appVersion['pretty_version']
            : $appVersion['semantic_version']
        ;
        $shortRef = substr($appVersion['reference'], 0, 7);

        return (self::UNKNOWN_VERSION === $appName)
            ? 'PHPLint'
            : sprintf(
                '%s version <info>%s</info> <comment>(%s)</comment> by overtrue and contributors.',
                $appName,
                $version,
                $shortRef,
            )
        ;
    }

    public function getVersion(): string
    {
        $appVersion = json_decode($this->value, true);

        return $this->getPrettyVersion($appVersion['semantic_version'] ?? self::UNKNOWN_VERSION);
    }

    /**
     * Provide same information, as `sebastian/version` package will do, but without adding extra dependency.
     *
     * @link https://github.com/sebastianbergmann/version
     * @link https://git-scm.com/docs/git-describe
     */
    private function getPrettyVersion(string $fallback): string
    {
        if (!file_exists(dirname(__DIR__, 2) . '/.git')) {
            return $fallback;
        }

        $process = new Process(['git', 'describe', '--tags', '--exact-match']);
        $process->run();

        if (!$process->isSuccessful()) {
            return self::UNKNOWN_VERSION;
        }

        return trim($process->getOutput());
    }
}
