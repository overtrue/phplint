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

use Composer\InstalledVersions;
use OutOfBoundsException;
use Overtrue\PHPLint\Extension\ExtensionInterface;
use Overtrue\PHPLint\Helper\DebugFormatterHelper;
use Overtrue\PHPLint\Helper\ProcessHelper;
use Overtrue\PHPLint\Output\ConsoleOutput;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Command\ListCommand;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use function array_keys;
use function explode;
use function in_array;
use function ltrim;
use function sprintf;

use const STDOUT;

/**
 * @author Overtrue
 * @author Laurent Laville (since v9.0)
 */
final class Application extends BaseApplication implements ApplicationInterface
{
    public const NAME = 'phplint';

    private const PACKAGE_NAME = 'overtrue/phplint';

    private EventDispatcherInterface $dispatcher;

    private string $defaultCommand = 'list';

    public function __construct()
    {
        parent::__construct(self::NAME, self::getPrettyVersion());
        $this->dispatcher = new EventDispatcher();
        $this->setDispatcher($this->dispatcher);  // mandatory because $dispatcher instance of BaseApplication is private
    }

    public function addExtensions(array $extensions): void
    {
        foreach ($extensions as $extension) {
            if (!$extension instanceof ExtensionInterface) {
                // accepts only valid extension that should implement this interface
                continue;
            }

            foreach ($extension->getCommands() as $command) {
                // adds extra commands if any provided
                $this->add($command);
            }

            // adds extra arguments and options if any provided by extension(s)
            $defaultCommand = $this->getDefaultCommand();

            if (null !== $defaultCommand) {
                $extensionDefinition = $extension->getDefinition();
                $definition = $defaultCommand->getDefinition();
                $definition->addArguments($extensionDefinition->getArguments());
                $definition->addOptions($extensionDefinition->getOptions());
                $defaultCommand->setDefinition($definition);
            }

            if ($extension instanceof EventSubscriberInterface) {
                $this->dispatcher->addSubscriber($extension);
            }
        }
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->dispatcher;
    }

    public function run(?InputInterface $input = null, ?OutputInterface $output = null): int
    {
        $output ??= new ConsoleOutput(STDOUT);

        return parent::run($input, $output);
    }

    /**
     * Mandatory because $defaultCommand instance of BaseApplication is private
     */
    public function setDefaultCommand(string $commandName, bool $isSingleCommand = false): static
    {
        $this->defaultCommand = explode('|', ltrim($commandName, '|'))[0];
        parent::setDefaultCommand($commandName, $isSingleCommand);
        return $this;
    }

    public function getDefaultCommand(): ?Command
    {
        if ('list' === $this->defaultCommand) {
            return null;
        }
        try {
            return $this->find($this->defaultCommand);
        } catch (CommandNotFoundException) {
            return null;
        }
    }

    protected function getDefaultCommands(): array
    {
        return [new HelpCommand(), new ListCommand()];
    }

    protected function getDefaultHelperSet(): HelperSet
    {
        return new HelperSet([
            new FormatterHelper(),
            new DebugFormatterHelper(),
            new ProcessHelper(),
        ]);
    }

    protected function getCommandName(InputInterface $input): ?string
    {
        $name = parent::getCommandName($input);
        return in_array($name, array_keys(parent::all())) ? $name : null;
    }

    private static function getPrettyVersion(): string
    {
        foreach (InstalledVersions::getAllRawData() as $installed) {
            if (!isset($installed['versions'][self::PACKAGE_NAME])) {
                continue;
            }

            $version = $installed['versions'][self::PACKAGE_NAME]['pretty_version']
                ?? $installed['versions'][self::PACKAGE_NAME]['version']
                ?? 'dev'
            ;

            $aliases = $installed['versions'][self::PACKAGE_NAME]['aliases'] ?? [];

            $reference = $installed['versions'][self::PACKAGE_NAME]['reference'];
            if (null === $reference) {
                return sprintf('%s', $aliases[0] ?? $version);
            }

            return sprintf(
                '%s@%s',
                $aliases[0] ?? $version,
                substr($reference, 0, 7)
            );
        }

        throw new OutOfBoundsException(sprintf('Package "%s" is not installed', self::PACKAGE_NAME));
    }
}
