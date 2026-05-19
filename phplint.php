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

use Overtrue\PHPLint\Command\LintCommand;
use Overtrue\PHPLint\Console\Application;
use Overtrue\PHPLint\Event\EventDispatcher;
use Overtrue\PHPLint\Extension\OutputManager;
use Overtrue\PHPLint\Extension\ProgressManager;
use Symfony\Component\Console\Input\ArgvInput;

$input = new ArgvInput();

if (true === $input->hasParameterOption(['--bootstrap'], true)) {
    $bootstrap = $input->getParameterOption('--bootstrap');
    if ($bootstrap) {
        require_once $bootstrap;
    }
}

$extensions[] = new OutputManager();
$extensions[] = new ProgressManager();

$dispatcher = new EventDispatcher($extensions);

$defaultCommand = new LintCommand($dispatcher);

$application = new Application();
$application->addCommands([$defaultCommand]);
$application->setDefaultCommand($defaultCommand->getName());
$application->addExtensions($extensions);
$application->setDispatcher($dispatcher);
$application->run($input);
