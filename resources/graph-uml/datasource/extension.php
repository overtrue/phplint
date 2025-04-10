<?php

use Overtrue\PHPLint\Extension\ExtensionInterface;

function dataSource(): Generator
{
    $classes = [
        ExtensionInterface::class,
    ];
    foreach ($classes as $class) {
        yield $class;
    }
}
