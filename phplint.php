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

use Overtrue\PHPLint\Command\DiagnoseCommand;
use Overtrue\PHPLint\Command\LintCommand;
use Overtrue\PHPLint\Configuration\ArgumentResolver;
use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Console\Application;
use Overtrue\PHPLint\Runtime\ConsoleApplicationRunner;
use Symfony\Component\Console\Input\ArgvInput;

$input = new ArgvInput();

if (true === $input->hasParameterOption(['--' . OptionDefinition::BOOTSTRAP, '-b'], true)) {
    $bootstrap = $input->getParameterOption(['--' . OptionDefinition::BOOTSTRAP, '-b']);
    if ($bootstrap) {
        require_once $bootstrap;
    }
}

$application = new Application();
$application->addCommands(
    [
        new DiagnoseCommand(),
        new LintCommand(),
    ]
);

$argumentResolver = new ArgumentResolver(ArgumentResolver::getDefaultValueResolvers());
$application->setArgumentResolver($argumentResolver);

$runner = new ConsoleApplicationRunner($application, 'dev', $input);
$runner->run();
