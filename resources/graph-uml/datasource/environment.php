<?php

use Overtrue\PHPLint\Environment\Provider\CI;
use Overtrue\PHPLint\Environment\Provider\Git;
use Overtrue\PHPLint\Environment\Provider\Php;
use Overtrue\PHPLint\Environment\Provider\Uname;
use Overtrue\PHPLint\Environment\ProviderData;
use Overtrue\PHPLint\Environment\ProviderInterface;
use Overtrue\PHPLint\Environment\Supplier;

function dataSource(): Generator
{
    $classes = [
        ProviderData::class,
        ProviderInterface::class,
        Supplier::class,
        CI::class,
        Git::class,
        Php::class,
        Uname::class,
    ];
    foreach ($classes as $class) {
        yield $class;
    }
}
