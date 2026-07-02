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

namespace Overtrue\PHPLint\Extension;

use Overtrue\PHPLint\Command\LintCommand;
use Overtrue\PHPLint\Configuration\ConsoleOptionsResolver;
use Overtrue\PHPLint\Configuration\FileOptionsResolver;
use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Event\AfterCheckingEvent;
use Overtrue\PHPLint\Output\ChainOutput;
use Overtrue\PHPLint\Output\FormatResolver;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Laurent Laville
 * @since Release 9.0.0 (renamed from OutputFormat to OutputManager since 9.8.0)
 */
final class OutputManager implements
    ExtensionInterface,
    EventSubscriberInterface
{
    private array $handlers = [];

    public function getName(): string
    {
        return ExtensionEnum::OUTPUT_MANAGER->value;
    }

    public static function getCommands(): array
    {
        return [];
    }

    public static function getDefinition(): InputDefinition
    {
        return new InputDefinition([
            new InputOption(
                OptionDefinition::OUTPUT_FORMAT,
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Format of requested reports'
            ),
            new InputOption(
                OptionDefinition::OUTPUT_FILE,
                'o',
                InputOption::VALUE_REQUIRED,
                'Generate an output to the specified path'
                . ' (<comment>default: ' . OptionDefinition::DEFAULT_STANDARD_OUTPUT_LABEL . '</comment>)'
            )
        ]);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => 'initialize',
            AfterCheckingEvent::class => 'finish',
        ];
    }

    public function initialize(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        if (!$command->getCode() instanceof LintCommand) {
            // this extension must be only available for lint command
            return;
        }

        $input = $event->getInput();

        if (true === $input->hasParameterOption(['--no-configuration'], true)) {
            $configResolver = new ConsoleOptionsResolver($input);
        } else {
            $configResolver = new FileOptionsResolver($input);
        }

        $this->handlers = (new FormatResolver())->resolve($configResolver, $event->getOutput());
    }

    public function finish(AfterCheckingEvent $event): void
    {
        $outputHandler = new ChainOutput($this->handlers);
        $outputHandler->format($event->getArgument('results'));
    }
}
