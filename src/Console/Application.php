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

use Overtrue\PHPLint\Command\InvokableCommand;
use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Configuration\Resolver\ArgumentResolverInterface;
use Overtrue\PHPLint\Configuration\Resolver\PluginValueResolver;
use Overtrue\PHPLint\Console\Attribute\ReflectionMember;
use Overtrue\PHPLint\Extension\ExtensionEnum;
use Overtrue\PHPLint\Extension\ExtensionInterface;
use Overtrue\PHPLint\Metadata\ApplicationVersion;
use Overtrue\PHPLint\Metadata\Metadata;
use Overtrue\PHPLint\Metadata\MetadataCollection;
use Overtrue\PHPLint\Output\ConsoleOutput;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionException;
use ReflectionFunction;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Command\ListCommand;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

use function array_filter;
use function iterator_to_array;
use function json_encode;

/**
 * @author Overtrue
 * @author Laurent Laville (since v9.0)
 */
final class Application extends BaseApplication implements ApplicationInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ArgumentResolverInterface $argumentResolver;

    private EventDispatcherInterface $dispatcher;

    private ?Stopwatch $stopwatch = null;

    private MetadataCollection $metadataCollection;

    public function __construct()
    {
        parent::__construct();

        $this->dispatcher = new EventDispatcher();
        // mandatory because $dispatcher instance of BaseApplication is private
        // and native getDispatcher() method is only available with release 8.1.0 of Symfony/Console
        $this->setDispatcher($this->dispatcher);

        $applicationVersion = Metadata::applicationVersion();
        $this->setVersion($applicationVersion->getVersion());
        $this->metadataCollection = new MetadataCollection($applicationVersion);
    }

    /**
     * Equivalent to setArgumentResolver() on Symfony 8.1 Console Component with compatible contract
     */
    public function setArgResolver(ArgumentResolverInterface $argumentResolver): void
    {
        $this->argumentResolver = $argumentResolver;
    }

    /**
     * @throws ReflectionException
     */
    public function addCommand(callable|SymfonyCommand $command): ?SymfonyCommand
    {
        if (!$command instanceof SymfonyCommand) {
            $code = $command;
            $command = new SymfonyCommand();
            $invokableCommand = new InvokableCommand($command, $code, $this->argumentResolver);
            $command->setName($invokableCommand->getName());
            $command->setDescription($invokableCommand->getDescription());
            $command->setCode($invokableCommand);

            $definition = $command->getDefinition();
            // mandatory, otherwise arguments and options of the $command won't be added
            $invokableCommand->configure($definition);
        }
        parent::addCommand($command);
        return $command;
    }

    public function getLongVersion(): string
    {
        /** @var ApplicationVersion $applicationVersion */
        $applicationVersion = $this->metadataCollection->getMetadata(ApplicationVersion::class);
        return $applicationVersion->getLongVersion();
    }

    public function getLogger(): LoggerInterface
    {
        if (null === $this->logger) {
            return new NullLogger();
        }
        return $this->logger;
    }

    public function getProfiler(): ?Stopwatch
    {
        return $this->stopwatch;
    }

    /**
     * Officially introduced with version 8.1 of Symfony Console Component
     * @link https://github.com/symfony/console/blob/2b468472ec5d0e4acbe00f97e62f6cd552509894/Application.php#L115-L118
     */
    public function getDispatcher(): ?EventDispatcherInterface
    {
        return $this->dispatcher;
    }

    public function getMetadata(): MetadataCollection
    {
        return $this->metadataCollection;
    }

    public function run(?InputInterface $input = null, ?OutputInterface $output = null): int
    {
        $output ??= new ConsoleOutput();

        if (!$output->getFormatter()->hasStyle('profile')) {
            $output->getFormatter()->setStyle(
                'profile',
                new OutputFormatterStyle('black', 'gray')
            );
        }

        return parent::run($input, $output);
    }

    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        if (true === $input->hasParameterOption(['--version', '-V'], true)) {
            $output->writeln($this->getLongVersion());
            return SymfonyCommand::SUCCESS;
        }

        try {
            // Makes ArgvInput::getFirstArgument() able to distinguish an option from an argument.
            $input->bind($this->getDefinition());
        } catch (ExceptionInterface) {
            // Errors must be ignored, full binding/validation happens later when the command is known.
        }

        $name = $this->getCommandName($input);
        if ('lint' === $name) {
            try {
                $command = $this->find($name);
                $this->loadPlugins($command, $input, $output);
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
    private function resolvePlugins(SymfonyCommand $command, InputInterface $input): iterable
    {
        $invokableCommand = $command->getCode();

        $code = $invokableCommand?->getCode() ?? null;

        if (null === $code) {
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
    private function loadPlugins(SymfonyCommand $command, InputInterface $input, OutputInterface $output): void
    {
        $dispatcher = $this->getDispatcher();
        $logger = $this->getLogger();

        $extensions = array_filter(
            iterator_to_array($this->resolvePlugins($command, $input))
        );

        $logger->notice(
            'The "{command}" command load the following extensions: {extensions}}',
            [
                'command' => $command->getName(),
                'extensions' => json_encode($extensions),
            ]
        );

        // loads all valid extension on fly, invalid ones will be reported by the Console ArgumentResolver component
        foreach ($extensions as $extensionName) {
            $extension = ExtensionEnum::factory($extensionName);

            if ($extensionName === ExtensionEnum::PROFILE_MANAGER) {
                $this->stopwatch = new Stopwatch(true);
            }

            if ($extension instanceof LoggerAwareInterface) {
                $extension->setLogger($logger);
            }

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
