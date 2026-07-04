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

namespace Overtrue\PHPLint\Console;

use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Configuration\Resolver\PluginValueResolver;
use Overtrue\PHPLint\Extension\ExtensionEnum;
use Overtrue\PHPLint\Extension\ExtensionInterface;
use Overtrue\PHPLint\Metadata\Metadata;
use Overtrue\PHPLint\Output\ConsoleOutput;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionException;
use ReflectionFunction;
use stdClass;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Attribute\Reflection\ReflectionMember;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Command\ListCommand;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

use function sprintf;
use function substr;

/**
 * @author Overtrue
 * @author Laurent Laville (since v9.0)
 */
final class Application extends BaseApplication implements ApplicationInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private stdClass $metaApplicationVersion;

    private EventDispatcherInterface $dispatcher;

    public function __construct()
    {
        parent::__construct();
        $this->dispatcher = new EventDispatcher();
        // mandatory because $dispatcher instance of BaseApplication is private
        $this->setDispatcher($this->dispatcher);

        $this->metaApplicationVersion = Metadata::applicationVersion()->describe();
        $this->setVersion($this->metaApplicationVersion->value);
    }

    public function getLongVersion(): string
    {
        $appName = $this->metaApplicationVersion->description;
        $appVersion = json_decode($this->metaApplicationVersion->value, true);

        $version = ('UNKNOWN' !== $appVersion['pretty_version'])
            ? $appVersion['pretty_version']
            : $appVersion['semantic_version']
        ;
        $shortRef = substr($appVersion['reference'], 0, 7);

        return ('UNKNOWN' === $appName)
            ? 'PHPLint'
            : sprintf(
                '%s <info>%s</info> <comment>(%s)</comment> by overtrue and contributors.',
                $appName, $version, $shortRef
            )
        ;
    }

    public function getLogger(): LoggerInterface
    {
        if (null === $this->logger) {
            return new NullLogger();
        }
        return $this->logger;
    }

    /**
     * Officially introduced with version 8.1 of Symfony Console Component
     * @link https://github.com/symfony/console/blob/2b468472ec5d0e4acbe00f97e62f6cd552509894/Application.php#L115-L118
     */
    public function getEventDispatcher(): ?EventDispatcherInterface
    {
        return $this->dispatcher;
    }

    public function run(?InputInterface $input = null, ?OutputInterface $output = null): int
    {
        $output ??= new ConsoleOutput();

        // @fixme Will be removed later when Symfony/Runtime component will be implemented
        if (null === $this->logger) {
            $this->setLogger(new \Symfony\Component\Console\Logger\ConsoleLogger($output));
        }

        return parent::run($input, $output);
    }

    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        if (true === $input->hasParameterOption(['--version', '-V'], true)) {
            $output->writeln($this->getLongVersion());
            return Command::SUCCESS;
        }

        try {
            // Makes ArgvInput::getFirstArgument() able to distinguish an option from an argument.
            $input->bind($this->getDefinition());
        } catch (ExceptionInterface) {
            // Errors must be ignored, full binding/validation happens later when the command is known.
        }

        $name = $this->getCommandName($input);
        if ($name) {
            try {
                $command = $this->find($name);
                $this->loadPlugins($command, $input);
            } catch (CommandNotFoundException) {
                // fallback to Symfony Base Application (parent) that will handle this error
            } catch (ReflectionException) {
                // plugins cannot be resolved/loaded
            }
        }

        return parent::doRun($input, $output);
    }

    /**
     * @throws ReflectionException
     */
    public function hasPlugin(string $pluginName, InputInterface $input): bool
    {
        $name = $this->getCommandName($input);
        if (!$name) {
            return false;
        }
        $command = $this->find($name);

        $extensions = $this->resolvePlugins($command, $input);

        foreach ($extensions as $extension) {
            if ($extension?->value === $pluginName) {
                return true;
            }
        }

        return false;
    }

    protected function getDefaultCommands(): array
    {
        return [new HelpCommand(), new ListCommand()];
    }

    protected function getDefaultHelperSet(): HelperSet
    {
        return new HelperSet([
            new FormatterHelper(),
        ]);
    }

    /**
     * @throws ReflectionException
     */
    private function resolvePlugins(Command $command, InputInterface $input): iterable
    {
        $code = $command->getCode();

        if ($code === null) {
            return [];
        }

        $reflector = new ReflectionFunction($code(...));
        $argumentName = OptionDefinition::EXTENSIONS;
        foreach ($reflector->getParameters() as $parameter) {
            if ($parameter->getName() !== $argumentName) {
                continue;
            }

            $pluginValueResolver = new PluginValueResolver();
            return $pluginValueResolver->resolve($argumentName, $input, new ReflectionMember($parameter));
        }

        return [];
    }

    /**
     * @throws ReflectionException
     */
    private function loadPlugins(Command $command, InputInterface $input): void
    {
        $dispatcher = $this->getDispatcher();

        $extensions = $this->resolvePlugins($command, $input);

        // loads all valid extension on fly, invalid ones will be reported by the Console ArgumentResolver component
        foreach ($extensions as $extensionName) {
            $extension = ExtensionEnum::factory($extensionName);

            if ($extension instanceof EventSubscriberInterface) {
                $dispatcher->addSubscriber($extension);
            }

            if ($extension instanceof ExtensionInterface) {
                $definition = $command->getDefinition();
                // adds new options defined by this $extension to the current $command
                foreach ($extension::getDefinition()->getOptions() as $option) {
                    $definition->addOption($option);
                }
                $command->setDefinition($definition);
            }
        }
    }
}
