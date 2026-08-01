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

namespace Overtrue\PHPLint\Output;

use Overtrue\PHPLint\Metadata\ApplicationVersion;
use Overtrue\PHPLint\Metadata\CacheOutput;
use Overtrue\PHPLint\Metadata\ConfigurationSettings;
use Overtrue\PHPLint\Metadata\MetadataCollection;
use Overtrue\PHPLint\Metadata\ProfilerOutput;
use Symfony\Component\Console\Output\StreamOutput;

use function json_decode;
use function json_encode;
use function sprintf;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;

/**
 * @author Laurent Laville
 * @since Release 9.0.0
 */
final class JsonOutput extends StreamOutput implements OutputInterface
{
    public function getName(): string
    {
        return 'json';
    }

    public function format(LinterOutput $results, MetadataCollection $metadataCollection): void
    {
        $failures = $results->getFailures();

        $result = [
            'status' => empty($failures) ? 'success' : 'failure',
            'failures' => $failures,
        ];

        /** @var ApplicationVersion $applicationVersion */
        $applicationVersion = $metadataCollection->getMetadata(ApplicationVersion::class);

        if (null !== $applicationVersion) {
            $result['application_version'] = [
                'long' => $applicationVersion->getLongVersion(),
                'short' => $applicationVersion->getVersion(),
            ];
        }

        /** @var ProfilerOutput $profilerResults */
        $profilerResults = $metadataCollection->getMetadata(ProfilerOutput::class);

        if (null != $profilerResults) {
            $results['initialization_time'] = $profilerResults->getInitializationTimeUsage();
            $results['total_execution_time'] = $profilerResults->getTotalExecutionTimeUsage();
            $results['total_memory_usage'] = $profilerResults->getTotalMemoryUsage();
            $result['time_usage'] = $profilerResults->getTimeUsage();
            $result['memory_usage'] = $profilerResults->getMemoryUsage();
        }

        $linterResults = $metadataCollection->getMetadata(\Overtrue\PHPLint\Metadata\LinterOutput::class);

        if (null != $linterResults) {
            $result['process_count'] = $linterResults->getProcessCount();
            $result['files_count'] = $linterResults->count();
        }

        /** @var CacheOutput $cacheResults */
        $cacheResults = $metadataCollection->getMetadata(CacheOutput::class);

        if (null != $cacheResults) {
            $cacheHits = $cacheResults->hits();
            $cacheMisses = $cacheResults->misses();

            $message = sprintf(
                '%d hit%s, %d miss%s',
                $cacheHits,
                $cacheHits > 1 ? 's' : '',
                $cacheMisses,
                $cacheMisses > 1 ? 'es' : ''
            );
            $result['cache_usage'] = $message;
        }

        /** @var ConfigurationSettings $config */
        $config = $metadataCollection->getMetadata(ConfigurationSettings::class);

        if (null !== $config) {
            $result['options_used'] = json_decode($config->describe('value'));
        }

        $flags = JSON_UNESCAPED_SLASHES;
        if ($this->isVerbose()) {
            $flags |= JSON_PRETTY_PRINT;
        }

        $jsonString = json_encode($result, $flags);
        $this->write($jsonString, true);
    }
}
