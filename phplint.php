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
use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Configuration\Resolver\CoreValueResolver;
use Overtrue\PHPLint\Configuration\Resolver\DefaultArgumentResolver;
use Overtrue\PHPLint\Configuration\Resolver\DefaultValueResolver;
use Overtrue\PHPLint\Console\Application;
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

$application = new Application($envConfig);

$namedResolvers = new DefaultValueResolver($logger, new CoreValueResolver($application, $output));

$argumentResolver = new DefaultArgumentResolver([], $namedResolvers);
$argumentResolver->setLogger($logger);

$application->setArgResolver($argumentResolver);

$application->setLogger($logger);
$application->addCommands([
    new DiagnoseCommand(),
    new LintCommand(),
]);

$runner = new ConsoleApplicationRunner($application, $envConfig->get('env', 'dev'), $input, $output);
$runner->run();
