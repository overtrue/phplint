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

$sourcePath = [dirname(__DIR__) . '/src', dirname(__DIR__) . '/tests'];
$excludePath = ['vendor'];
$fileExtensions = ['php'];

$finder = new Finder(paths: $sourcePath, excludes: $excludePath, fileExtensions: $fileExtensions);
$linter = new Linter(showWarning: true);
$results = $linter->lintFiles($finder->getFiles());

var_dump($results->getErrors());

var_dump($results->getWarnings());
