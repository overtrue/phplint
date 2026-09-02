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

require_once dirname(__DIR__) . '/autoload.php';

use Overtrue\PHPLint\Finder;
use Overtrue\PHPLint\Linter;

$sourcePath = [__DIR__ . '/empty_dir', __DIR__ . '/missing_dir'];
$fileExtensions = ['php'];

$finder = new Finder(paths: $sourcePath, fileExtensions: $fileExtensions);
$linter = new Linter();
try {
    $results = $linter->lintFiles($finder->getFiles());
} catch (Throwable $e) {
    $results = [];
}

if (count($results) === 0) {
    printf(
        "Could not find any files to lint with this Finder %s" . PHP_EOL,
        json_encode($finder, JSON_UNESCAPED_SLASHES)
    );
}
