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
use stdClass;

use function sprintf;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
final class ApplicationVersion extends Metadata
{
    private const PACKAGE_NAME = 'overtrue/phplint';

    private string $prettyVersion;
    private string $reference;

    public function __construct()
    {
        $installed = InstalledVersions::getAllRawData()[0];

        if (!isset($installed['versions'][self::PACKAGE_NAME])) {
            throw new OutOfBoundsException(sprintf('Package "%s" is not installed', self::PACKAGE_NAME));
        }

        $this->prettyVersion = $installed['versions'][self::PACKAGE_NAME]['pretty_version'] ?? 'UNKNOWN';
        $this->value = $installed['versions'][self::PACKAGE_NAME]['version'] ?? 'dev';

        $aliases = $installed['versions'][self::PACKAGE_NAME]['aliases'] ?? [];

        $reference = $installed['versions'][self::PACKAGE_NAME]['reference'];
        if (null === $reference) {
            $reference = $aliases[0] ?? 'UNKNOWN';
        }
        $this->reference = $reference;

        $this->name = 'application_version';
        $this->description = 'PHPLint Console Application version';
    }

    public function describe(): stdClass
    {
        $metadata = parent::describe();
        $metadata->reference = $this->reference;
        $metadata->pretty_version = $this->prettyVersion;

        return $metadata;
    }
}
