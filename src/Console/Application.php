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

namespace Overtrue\PHPLint\Console;

use Composer\InstalledVersions;
use OutOfBoundsException;
use Overtrue\PHPLint\Helper\DebugFormatterHelper;
use Overtrue\PHPLint\Helper\ProcessHelper;
use Overtrue\PHPLint\Output\ConsoleOutput;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Command\ListCommand;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

use function array_keys;
use function dirname;
use function file_exists;
use function in_array;
use function sprintf;
use function substr;
use function trim;

use const STDOUT;

/**
 * @author Overtrue
 * @author Laurent Laville (since v9.0)
 */
final class Application extends BaseApplication
{
    public const NAME = 'phplint';

    private const PACKAGE_NAME = 'overtrue/phplint';
    private const UNKNOWN_VERSION = 'UNKNOWN';

    private array $versionInfo;

    public function __construct()
    {
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

        $this->versionInfo = [
            'semantic_version' => $version,
            'pretty_version' => $prettyVersion,
            'reference' => $reference,
        ];

        parent::__construct(self::NAME, $this->versionInfo['pretty_version']);
    }

    public function getLongVersion(): string
    {
        $appName = self::NAME;
        $appVersion = $this->versionInfo;

        $version = (self::UNKNOWN_VERSION !== $appVersion['pretty_version'])
            ? $appVersion['pretty_version']
            : $appVersion['semantic_version']
        ;
        $shortRef = substr($appVersion['reference'], 0, 7);

        return sprintf(
            '%s version <info>%s</info> <comment>(%s)</comment> by overtrue and contributors.',
            $appName,
            $version,
            $shortRef,
        );
    }

    public function run(?InputInterface $input = null, ?OutputInterface $output = null): int
    {
        $output ??= new ConsoleOutput(STDOUT);

        return parent::run($input, $output);
    }

    protected function getDefaultCommands(): array
    {
        return [new HelpCommand(), new ListCommand()];
    }

    protected function getDefaultHelperSet(): HelperSet
    {
        return new HelperSet([
            new FormatterHelper(),
            new DebugFormatterHelper(),
            new ProcessHelper(),
        ]);
    }

    protected function getCommandName(InputInterface $input): ?string
    {
        $name = parent::getCommandName($input);
        return in_array($name, array_keys(parent::all())) ? $name : null;
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
