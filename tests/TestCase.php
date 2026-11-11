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

use Overtrue\PHPLint\Configuration\FileOptionsResolver;
use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Configuration\Resolver\ConfigValueResolver;
use Overtrue\PHPLint\Configuration\Resolver\JobValueResolver;
use Overtrue\PHPLint\Environment\EnvConfig;
use Overtrue\PHPLint\Extension\ExtensionEnum;
use Overtrue\PHPLint\Runtime\ConsoleApplicationRunner;

use Psr\Log\NullLogger;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;

use ReflectionException;

/**
 * @author Laurent Laville
 * @since Release 9.0.0
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected Application $application;

    /**
     * @throws ReflectionException
     */
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

    /**
     * @throws ReflectionException
     */
    protected function getOptionsResolver(array $arguments): FileOptionsResolver
    {
        $application = $this->getApplication();

        $command = $application->find('lint');
        $command->mergeApplicationDefinition();

        $this->loadPlugin(ExtensionEnum::OUTPUT_MANAGER->value, $command);

        $definition = $command->getDefinition();

        $input = new ArrayInput($arguments);
        $input->bind($definition);

        $configFile = $arguments['--' . OptionDefinition::CONFIGURATION] ?? 'never';

        $configValueResolver = new ConfigValueResolver(
            [OptionDefinition::CONFIGURATION],
            [OptionDefinition::CONFIGURATION => $configFile],
        );

        return new FileOptionsResolver($input, [], $configValueResolver);
    }

    private function loadPlugin(string $extensionName, Command $command): void
    {
        $extension = ExtensionEnum::factory($extensionName);

        $definition = $command->getDefinition();
        // adds new options defined by this $extension to the $command
        foreach ($extension::getDefinition()->getOptions() as $option) {
            if (!$definition->hasOption($option->getName())) {
                $definition->addOption($option);
            }
        }
        $command->setDefinition($definition);
    }
}
