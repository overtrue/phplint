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

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Overtrue\PHPLint\Command\LintCommand;
use Overtrue\PHPLint\Configuration\ConsoleOptionsResolver;
use Overtrue\PHPLint\Event\EventDispatcher;
use Overtrue\PHPLint\Finder;
use Overtrue\PHPLint\Linter;
use Symfony\Component\Console\Input\ArrayInput;

$dispatcher = new EventDispatcher([]);

$arguments = [
    'path' => [__DIR__ . '/empty_dir', __DIR__ . '/missing_dir'],
    '--no-configuration' => true,
];
$command = new LintCommand($dispatcher);
$input = new ArrayInput($arguments, $command->getDefinition());
$configResolver = new ConsoleOptionsResolver($input);

$finder = (new Finder($configResolver)); //->getFiles();
$linter = new Linter($configResolver, $dispatcher);
$results = $linter->lintFiles($finder->getFiles());

var_dump("Files checked :", count($results));

if (count($results) === 0) {
    throw new LogicException(
        sprintf("Could not find any files to lint with this Finder %s", json_encode($finder))
    );
}
