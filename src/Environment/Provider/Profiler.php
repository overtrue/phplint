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

namespace Overtrue\PHPLint\Environment\Provider;

use Overtrue\PHPLint\Console\SectionEnum;
use Overtrue\PHPLint\Environment\ProviderData;
use Overtrue\PHPLint\Environment\ProviderInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
final class Profiler implements ProviderInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @return ProviderData[]
     */
    public function describe(): array
    {
        $packageName = 'symfony/stopwatch';
        $installed = true;

        // @see https://getcomposer.org/doc/07-runtime.md#installed-versions
        if (!\Composer\InstalledVersions::isInstalled($packageName)) {
            $this->logger->warning(
                'Package "{packageName}" is not installed.',
                [
                    '__section__' => SectionEnum::DEPENDENCY->label(),
                    'packageName' => $packageName
                ]
            );
            $installed = false;
        }

        return [
            new ProviderData($packageName, $installed ? 'YES' : 'NO', 'Profiler installed')
        ];
    }
}
