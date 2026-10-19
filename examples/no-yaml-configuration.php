<?php

/*
 * This file is part of the overtrue/phplint package
 *
 * (c) overtrue
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

require_once dirname(__DIR__) . '/autoload.php';

use Overtrue\PHPLint\Cache;
use Overtrue\PHPLint\Environment\EnvConfig;
use Overtrue\PHPLint\Finder;
use Overtrue\PHPLint\Linter;
use Overtrue\PHPLint\Metadata\MetadataCollection;
use Symfony\Component\Cache\Adapter\NullAdapter;

$sourcePath = [dirname(__DIR__) . '/src', dirname(__DIR__) . '/tests'];
$excludePath = ['vendor'];
$fileExtensions = ['php'];

$finder = new Finder(paths: $sourcePath, excludes: $excludePath, fileExtensions: $fileExtensions);
$linter = new Linter(
    cache: new Cache(new NullAdapter()),
    showWarning: true,
);

$envConfig = new EnvConfig();
$metadataCollection = new MetadataCollection();

$results = $linter->lintFiles($finder->getFiles(), null, $metadataCollection);

var_dump("Files checked :", count($results));

var_dump("Errors detected :", $results->getErrors());

var_dump("Warnings detected :", $results->getWarnings());
