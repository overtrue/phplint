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
use Overtrue\PHPLint\Console\ConsoleLogger;
use Overtrue\PHPLint\Console\SectionEnum;
use Overtrue\PHPLint\Environment\EnvConfig;
use Overtrue\PHPLint\Output\ConsoleOutput;
use Overtrue\PHPLint\Runtime\ConsoleApplicationRunner;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\ArgvInput;

$input = new ArgvInput();

$output = new ConsoleOutput();

// Default colors skin :
// If you want to override, use the bootstrapping feature
$styles = [
    SectionEnum::DEFAULT->value => new OutputFormatterStyle('black', 'cyan'),
    SectionEnum::COMMAND->value => new OutputFormatterStyle('white', 'blue'),
    SectionEnum::ARGUMENT->value => new OutputFormatterStyle('yellow', 'blue'),
    SectionEnum::PLUGIN->value => new OutputFormatterStyle('white', 'red'),
    SectionEnum::PROFILE->value => new OutputFormatterStyle('black', 'gray'),
    SectionEnum::ENVIRONMENT->value => new OutputFormatterStyle('yellow', 'blue'),
    SectionEnum::EVENT->value => new OutputFormatterStyle('white', 'magenta'),
    SectionEnum::METADATA->value => new OutputFormatterStyle('black', 'yellow'),
];

foreach ($styles as $name => $style) {
    if (!$output->getFormatter()->hasStyle($name)) {
        $output->getFormatter()->setStyle($name, $style);
    }
}

$envConfig = new EnvConfig();

$loggerClass = $envConfig->get('logger', ConsoleLogger::class);

if (true === $input->hasParameterOption(['--' . OptionDefinition::BOOTSTRAP, '-b'], true)) {
    $bootstrap = $input->getParameterOption(['--' . OptionDefinition::BOOTSTRAP, '-b']);
    if ($bootstrap) {
        require_once $bootstrap;
    }
}

$logger = new $loggerClass($output);

$runner = new ConsoleApplicationRunner($logger, $envConfig, $input, $output);
$runner->run();
