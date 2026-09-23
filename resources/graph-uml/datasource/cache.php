<?php

use Overtrue\PHPLint\Cache;
use Overtrue\PHPLint\Extension\CacheManager;

function dataSource(): Generator
{
    $classes = [
        CacheManager::class,
        Cache::class,
    ];
    foreach ($classes as $class) {
        yield $class;
    }
}
