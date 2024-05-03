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
use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Console\Application;
use Overtrue\PHPLint\Event\EventDispatcher;
use Overtrue\PHPLint\Extension\OutputFormat;
use Overtrue\PHPLint\Extension\ProgressBar;
use Overtrue\PHPLint\Extension\ProgressIndicator;
use Overtrue\PHPLint\Extension\ProgressPrinter;
use Symfony\Component\Console\Input\ArgvInput;

$input = new ArgvInput();

$extensions = [new ProgressPrinter()];

if (true === $input->hasParameterOption(['--no-progress'], true)) {
    $extensions = [];
} elseif (true === $input->hasParameterOption(['--progress'], true)) {
    $progress = $input->getParameterOption('--progress', 'printer');

    $extensions = match ($progress) {
        'bar' => [new ProgressBar()],
        'indicator' => [new ProgressIndicator()],
        default => [new ProgressPrinter()],
    };
}

$formats = [
    OptionDefinition::LOG_JSON,
    OptionDefinition::LOG_JUNIT,
];

if (\class_exists('\Bartlett\Sarif\SarifLog')) {
    // only when Composer development dependencies were installed too!
    $formats[] = OptionDefinition::LOG_SARIF;
}

$extensions[] = new OutputFormat($formats);

$dispatcher = new EventDispatcher($extensions);

$defaultCommand = new LintCommand($dispatcher);

$application = new Application();
$application->add($defaultCommand);
$application->setDefaultCommand($defaultCommand->getName());
$application->setDispatcher($dispatcher);
$application->run($input);
