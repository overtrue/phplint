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

use Overtrue\PHPLint\Extension\ProfileManager;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Stopwatch\Stopwatch;

use function json_encode;

use const JSON_UNESCAPED_SLASHES;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
final class ProfilerOutput extends Metadata
{
    public const METADATA_ID = 'profiler_results';

    private array $usages = [];

    public function __construct(private readonly Stopwatch $stopwatch)
    {
        $profilingEvent = $stopwatch->getEvent(ProfileManager::PROFILING_EVENT);

        $totalTime = $profilingEvent->getDuration();

        $lintFilesEvent = $stopwatch->getEvent(ProfileManager::LINT_FILES_EVENT);
        $lintingTime = $lintFilesEvent->getDuration();

        $initializationTime = Helper::formatTime( ($totalTime - $lintingTime) / 1000);
        $timeUsage = Helper::formatTime($lintingTime / 1000);
        $memoryUsage = Helper::formatMemory($lintFilesEvent->getMemory());

        $this->usages = [
            'initialization_time' => $initializationTime,
            'total_execution_time' => Helper::formatTime($totalTime / 1000),
            'total_memory_usage' => Helper::formatMemory($profilingEvent->getMemory()),
            'time_usage' => $timeUsage,
            'memory_usage' => $memoryUsage,
        ];

        $this->description = 'Profiler analysis results';
        $this->value = json_encode($this->usages, JSON_UNESCAPED_SLASHES);
    }

    public function getStopwatch(): Stopwatch
    {
        return $this->stopwatch;
    }

    public function getInitializationTimeUsage(): string
    {
        return $this->usages['initialization_time'];
    }

    public function getTotalExecutionTimeUsage(): string
    {
        return $this->usages['total_execution_time'];
    }

    public function getTotalMemoryUsage(): string
    {
        return $this->usages['total_memory_usage'];
    }

    public function getTimeUsage(): string
    {
        return $this->usages['time_usage'];
    }

    public function getMemoryUsage(): string
    {
        return $this->usages['memory_usage'];
    }
}
