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

use Overtrue\PHPLint\Cache;
use stdClass;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Stopwatch\Stopwatch;

use function property_exists;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
abstract class Metadata
{
    protected string $value;
    protected ?string $description;

    public function describe(?string $part = null): stdClass|string|null
    {
        $metadata = new stdClass();
        $metadata->name = static::METADATA_ID;
        $metadata->value = $this->value;
        $metadata->description = $this->description;

        if ($part && property_exists($metadata, $part)) {
            return $metadata->$part;
        }

        return $metadata;
    }

    public static function applicationVersion(): ApplicationVersion
    {
        return new ApplicationVersion();
    }

    public static function configurationSettings(array $settings): ConfigurationSettings
    {
        return new ConfigurationSettings($settings);
    }

    public static function linterResults(array $results, Finder $finder): LinterOutput
    {
        return new LinterOutput($results, $finder);
    }

    public static function profilerResults(Stopwatch $stopwatch): ProfilerOutput
    {
        return new ProfilerOutput($stopwatch);
    }

    public static function cacheResults(Cache $cache): CacheOutput
    {
        return new CacheOutput($cache);
    }
}
