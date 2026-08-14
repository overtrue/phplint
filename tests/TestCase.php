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
use Overtrue\PHPLint\Command\LintCommand;
use Overtrue\PHPLint\Configuration\FileOptionsResolver;
use Overtrue\PHPLint\Configuration\Resolver\CoreValueResolver;
use Overtrue\PHPLint\Configuration\Resolver\DefaultArgumentResolver;
use Overtrue\PHPLint\Configuration\Resolver\DefaultValueResolver;
use Overtrue\PHPLint\Configuration\Resolver\JobValueResolver;
use Overtrue\PHPLint\Console\Application;
use Overtrue\PHPLint\Environment\EnvConfig;
use Overtrue\PHPLint\Extension\OutputManager;
use Overtrue\PHPLint\Runtime\ConsoleApplicationRunner;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @author Laurent Laville
 * @since Release 9.0.0
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected \Symfony\Component\Console\Application $application;

    protected function setUp(): void
    {
        $application = new Application(new EnvConfig());

        $valueResolver = new class(
            new NullLogger(),
            new CoreValueResolver($application, new BufferedOutput())
        ) extends DefaultValueResolver {
            public function __construct(LoggerInterface $logger, CoreValueResolver $coreValueResolver)
            {
                parent::__construct($logger, $coreValueResolver);
                // to avoid auto CPU detection
                $this->valueResolvers[JobValueResolver::class] = new JobValueResolver(10);
            }
        };

        $argumentResolver = new DefaultArgumentResolver([], $valueResolver);

        $application->setArgResolver($argumentResolver);
        $application->addCommand(new LintCommand());

        $this->application = $application;
    }

    protected function getApplication(): \Symfony\Component\Console\Application
    {
        $runner = new ConsoleApplicationRunner($this->application, 'tests');
        return $runner->getApplication();
    }

    protected function getOptionsResolver(array $arguments): FileOptionsResolver
    {
        $application = $this->getApplication();

        $command = $application->find('lint');
        $command->mergeApplicationDefinition();

        // add this extension for --output-format and --output-file additional options
        $outputManager = new OutputManager();
        $extensionDefinition = $outputManager->getDefinition();
        $definition = $command->getDefinition();
        $definition->addOptions($extensionDefinition->getOptions());

        $input = new ArrayInput($arguments);
        $input->bind($definition);

        $output = new BufferedOutput();

        /** @var InvokableCommand $invokableCommand */
        $invokableCommand = $command->getCode();

        $parameters = $invokableCommand->getArguments($input, $output);

        return new FileOptionsResolver($input, $parameters);
    }
}
