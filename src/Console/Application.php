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

use Overtrue\PHPLint\Cache;
use Overtrue\PHPLint\Command\InvokableCommand;
use Overtrue\PHPLint\Configuration\FileOptionsResolver;
use Overtrue\PHPLint\Configuration\Resolver\ArgumentResolverInterface;
use Overtrue\PHPLint\Environment\EnvConfigInterface;
use Overtrue\PHPLint\Extension\CacheManager;
use Overtrue\PHPLint\Extension\ExtensionEnum;
use Overtrue\PHPLint\Extension\ExtensionInterface;
use Overtrue\PHPLint\Metadata\ApplicationVersion;
use Overtrue\PHPLint\Metadata\Metadata;
use Overtrue\PHPLint\Metadata\MetadataCollection;
use Overtrue\PHPLint\Output\ConsoleOutput;
use Overtrue\PHPLint\Runtime\ConsoleApplicationRunner;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionException;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Command\ListCommand;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

use Throwable;
use function json_encode;

/**
 * @author Overtrue
 * @author Laurent Laville (since v9.0)
 */
final class Application extends BaseApplication implements
    ApplicationInterface,
    LoggerAwareInterface,
    EventSubscriberInterface
{
    use LoggerAwareTrait;

    private ArgumentResolverInterface $argumentResolver;

    private EventDispatcherInterface $dispatcher;

    private ?Stopwatch $stopwatch = null;

    private ?MetadataCollection $metadataCollection = null;

    private ExtensionEnum $extensions;

    private ?Cache $cache = null;

    public function __construct(private EnvConfigInterface $envConfig)
    {
        parent::__construct();

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber($this);
        // mandatory because $dispatcher instance of BaseApplication is private
        // and native getDispatcher() method is only available with release 8.1.0 of Symfony/Console
        $this->setDispatcher($this->dispatcher);
    }

    /**
     * Equivalent to setArgumentResolver() on Symfony 8.1 Console Component with compatible contract
     */
    public function setArgResolver(ArgumentResolverInterface $argumentResolver): void
    {
        $this->argumentResolver = $argumentResolver;
    }

    public function setMetadata(MetadataCollection $metadataCollection): void
    {
        $this->metadataCollection = $metadataCollection;
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

    public function getCache(): Cache
    {
        return $this->cache ?? (new CacheManager())->getCacheInstance();
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

    public function getMetadata(array $settings = []): MetadataCollection
    {
        $metadataCollection =  $this->metadataCollection ?? new MetadataCollection(
            Metadata::applicationVersion(),
        );
        if (!empty($settings)) {
            $settings['mode'] = $this->getEnvConfig()->get('mode', 'off');
            $metadataCollection->add(Metadata::configurationSettings($settings));
        }
        return $metadataCollection;
    }

    public function getEnvConfig(): EnvConfigInterface
    {
        return $this->envConfig;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => ['initialize', 200],
            ConsoleEvents::ERROR => 'error',
        ];
    }

    public function run(?InputInterface $input = null, ?OutputInterface $output = null): int
    {
        $output ??= new ConsoleOutput();

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

        return parent::run($input, $output);
    }

    /**
     * @throws ReflectionException
     * @throws Throwable
     */
    protected function doRunCommand(Command $command, InputInterface $input, OutputInterface $output): int
    {
        $extensions = ConsoleApplicationRunner::getAllowedPlugins($this->envConfig, $input);

        $this->loadPlugins($extensions, $command);

        return parent::doRunCommand($command, $input, $output);
    }

    /**
     * @internal For debugging purpose only, until 9.8.0-rc.1 was released
     */
    public function error(ConsoleErrorEvent $event): void
    {
        $error = $event->getError();

        if ($this->envConfig->get('PLINT_DUMP', false)) {
            \var_export($error);
        } else {
            \var_dump($error);
        }
    }

    /**
     * @throws ReflectionException
     */
    public function initialize(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        $input  = $event->getInput();

        $invokableCommand = $command->getCode();
        $parameters = $invokableCommand?->getArguments($input) ?? [];
        $configResolver = new FileOptionsResolver($input, $parameters);

        $metadataCollection = $this->getMetadata($configResolver->getOptions());
        $metadataCollection->describe($this->getLogger());
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
    private function loadPlugins(array $extensions, SymfonyCommand $command): void
    {
        $dispatcher = $this->getDispatcher();
        $logger = $this->getLogger();

        $message = sprintf(
            '<comment>%s</comment> %s',
            'The "{command}" command load the following extensions',
            ': {extensions}'
        );

        $logger->notice(
            $message,
            [
                '__section__' => SectionEnum::PLUGIN->label(),
                '__style__' => SectionEnum::PLUGIN->value,
                'command' => $command->getName(),
                'extensions' => json_encode($extensions),
            ]
        );

        // loads all valid extension on fly, invalid ones will be reported by the Console ArgumentResolver component
        foreach ($extensions as $extensionName) {
            $extension = ExtensionEnum::factory($extensionName);

            if ($extensionName == ExtensionEnum::CACHE_MANAGER->value) {
                $this->cache = $extension::getCacheInstance();
            }

            if ($extensionName === ExtensionEnum::PROFILE_MANAGER->value) {
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
                    if (!$definition->hasOption($option->getName())) {
                        $definition->addOption($option);
                    }
                }
                $command->setDefinition($definition);
            }
        }
    }
}
