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
#240
use Overtrue\PHPLint\Command\LintCommand;
use Overtrue\PHPLint\Configuration\ConsoleOptionsResolver;
use Overtrue\PHPLint\Finder;
use Overtrue\PHPLint\Linter;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\EventDispatcher\EventDispatcher;

$dispatcher = new EventDispatcher();

$arguments = [
    'path' => [dirname(__DIR__) . '/src', dirname(__DIR__) . '/tests'],
    '--no-configuration' => true,
    '--no-cache' => true,
    '--exclude' => ['vendor'],
    '--extensions' => ['php'],
    '--warning' => true,
];
$command = new LintCommand();
$input = new ArrayInput($arguments, $command->getDefinition());

$configResolver = new ConsoleOptionsResolver($input);

$finder = new Finder($configResolver);
$linter = new Linter($configResolver, $dispatcher);
$results = $linter->lintFiles($finder->getFiles());

var_dump($results->getErrors());

var_dump($results->getWarnings());
