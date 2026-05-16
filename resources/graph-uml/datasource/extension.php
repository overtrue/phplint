<?php

/*
 * This file is part of the overtrue/phplint package
 *
 * (c) overtrue
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Overtrue\PHPLint\Extension\ExtensionInterface;

function dataSource(): Generator
{
    $classes = [
        ExtensionInterface::class
    ];
    foreach ($classes as $class) {
        yield $class;
    }
}
