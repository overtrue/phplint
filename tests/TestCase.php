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

namespace Overtrue\PHPLint\Tests;

use Overtrue\PHPLint\Command\InvokableCommand;
use Overtrue\PHPLint\Configuration\FileOptionsResolver;
use Overtrue\PHPLint\Configuration\Resolver\JobValueResolver;
use Overtrue\PHPLint\Environment\EnvConfig;
use Overtrue\PHPLint\Runtime\ConsoleApplicationRunner;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * @author Laurent Laville
 * @since Release 9.0.0
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected Application $application;

    protected function setUp(): void
    {
        $envConfig = new EnvConfig();

        $logger = new NullLogger();

        $commandName = 'lint';
        $input = new ArrayInput(['command' => $commandName]);

        $argumentValueResolvers = [
            // to avoid auto CPU detection
            JobValueResolver::class => new JobValueResolver(10),
        ];

        $runner = new ConsoleApplicationRunner($logger, $envConfig, $input, null, $argumentValueResolvers);
        $this->application = $runner->getApplication();
    }

    protected function getApplication(): Application
    {
        return $this->application;
    }

    protected function getOptionsResolver(array $arguments): FileOptionsResolver
    {
        $application = $this->getApplication();

        $command = $application->find('lint');
        $command->mergeApplicationDefinition();

        $definition = $command->getDefinition();

        $input = new ArrayInput($arguments);
        $input->bind($definition);

        /** @var InvokableCommand $invokableCommand */
        $invokableCommand = $command->getCode();

        $parameters = $invokableCommand->getArguments($input);

        return new FileOptionsResolver($input, $parameters);
    }
}
