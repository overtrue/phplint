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

use function json_encode;

use const JSON_UNESCAPED_SLASHES;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
final class CacheOutput extends Metadata
{
    public const METADATA_ID = 'cache_results';

    public function __construct(private readonly Cache $cache)
    {
        $this->description = 'Cache analysis results';
        $this->value = json_encode($cache->__debugInfo(), JSON_UNESCAPED_SLASHES);
    }

    /**
     * Returns list of files that were not checked again (because fingerprint is same)
     */
    public function hits(): int
    {
        return $this->cache->__debugInfo()['hits'];
    }

    /**
     * Returns list of files that were check since the last scan.
     */
    public function misses(): int
    {
        return $this->cache->__debugInfo()['misses'];
    }

    public function innerAdapterClass(): string
    {
        return $this->cache->__debugInfo()['inner-adapter'];
    }
}
