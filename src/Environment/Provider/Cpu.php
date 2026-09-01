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

use Fidry\CpuCoreCounter\CpuCoreCounter;
use Overtrue\PHPLint\Console\SectionEnum;
use Overtrue\PHPLint\Environment\ProviderData;
use Overtrue\PHPLint\Environment\ProviderInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

use function json_encode;

use const JSON_UNESCAPED_SLASHES;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
class Cpu implements ProviderInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(private ?int $countLimit = null)
    {
    }

    public function describe(): ?array
    {
        $packageName = 'fidry/cpu-core-counter';

        // @see https://getcomposer.org/doc/07-runtime.md#installed-versions
        if (!\Composer\InstalledVersions::isInstalled($packageName)) {
            $this->logger->warning(
                'Package "{packageName}" is not installed.',
                [
                    '__section__' => SectionEnum::DEPENDENCY->label(),
                    'packageName' => $packageName
                ]
            );
            return null;
        }

        $cpuDetector = new CpuCoreCounter();

        // Reserve 1 core for the main orchestrating process
        $parallelisationResult = $cpuDetector->getAvailableForParallelisation(1, $this->countLimit);

        $variables = [
            'passedReservedCpus' => 'CPU reserved for orchestrating process',
            'passedCountLimit' => 'Maximum CPU to use (null = no limit, use all logical core available)',
            'availableCpus' => 'CPU logical core available',
            'totalCoresCount' => 'CPU max core available',
        ];

        $data = [];
        foreach ($variables as $key => $desc) {
            $data[] = new ProviderData(
                'parallel.' . $key,
                json_encode($parallelisationResult->$key, JSON_UNESCAPED_SLASHES),
                $desc
            );
        }
        return $data;
    }
}
