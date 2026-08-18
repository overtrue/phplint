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

use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Environment\EnvConfig;
use Overtrue\PHPLint\Output\ConsoleOutput;
use Overtrue\PHPLint\Runtime\ConsoleApplicationRunner;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Logger\ConsoleLogger;

$input = new ArgvInput();

if (true === $input->hasParameterOption(['--' . OptionDefinition::BOOTSTRAP, '-b'], true)) {
    $bootstrap = $input->getParameterOption(['--' . OptionDefinition::BOOTSTRAP, '-b']);
    if ($bootstrap) {
        require_once $bootstrap;
    }
}

$output = new ConsoleOutput();
$logger = new ConsoleLogger($output);
$envConfig = new EnvConfig();

$runner = new ConsoleApplicationRunner($logger, $envConfig, $input, $output);
$runner->run();
