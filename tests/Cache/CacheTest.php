<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Tests\Cache;

use Overtrue\PHPLint\Cache;
use Overtrue\PHPLint\Tests\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use function md5_file;
use function sha1_file;

/**
 * @author Laurent Laville
 * @since Release 7.0.0
 */
final class CacheTest extends TestCase
{
    /**
     * @covers \Overtrue\PHPLint\Cache::isFresh
     */
    public function testCacheMiss(): void
    {
        $cache = new Cache(new ArrayAdapter());

        $this->assertFalse($cache->isFresh(__FILE__));
    }

    /**
     * @covers \Overtrue\PHPLint\Cache::isFresh
     */
    public function testCacheHit(): void
    {
        $cache = new Cache(new ArrayAdapter());
        $cache->isFresh(__FILE__);  // cache missed

        $this->assertTrue($cache->isFresh(__FILE__));
    }
}
