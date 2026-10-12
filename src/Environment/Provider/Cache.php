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

use Overtrue\PHPLint\Cache as CacheImplementation;
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
class Cache implements ProviderInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(private readonly CacheImplementation $cache)
    {
    }

    public function describe(): ?array
    {
        $variables = [
            'inner-adapter' => 'Class that implement the actual caching mechanism',
            'cache-pool' => 'Logical repository of cache items',
            'hits' => 'Items found in the cache',
            'misses' => 'Items not found or expired in the cache',
        ];

        $data = [];
        foreach ($this->cache->__debugInfo() as $setting => $value) {
            if ('calls' === $setting) {
                continue;
            }
            $data[] = new ProviderData(
                $setting,
                json_encode($value, JSON_UNESCAPED_SLASHES),
                $variables[$setting] ?? null
            );
        }
        return $data;
    }
}
