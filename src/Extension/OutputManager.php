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
use Overtrue\PHPLint\Event\AfterCheckingInterface;
use Overtrue\PHPLint\Output\ChainOutput;
use Overtrue\PHPLint\Output\FormatResolver;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use function get_class;
use function ltrim;
use function preg_replace;
use function strrchr;
use function strtolower;
use function substr;

/**
 * @author Laurent Laville
 * @since Release 9.0.0
 */
final class OutputManager implements
    ExtensionInterface,
    EventSubscriberInterface,
    AfterCheckingInterface
{
    private array $handlers = [];

    public function getName(): string
    {
        $shortClassName = substr(strrchr(get_class($this), '\\'), 1);
        // @see https://stackoverflow.com/questions/1993721/how-to-convert-pascalcase-to-snake-case
        return ltrim(strtolower(preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '_$0', $shortClassName)), '_');
    }

    public static function getCommands(): array
    {
        return [];
    }

    public static function getDefinition(): InputDefinition
    {
        return new InputDefinition([
            new InputOption(
                'format',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Format of requested reports'
            ),
            new InputOption(
                'output',
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
            ConsoleEvents::COMMAND => 'initFormat',
            AfterCheckingEvent::class => 'afterChecking',
        ];
    }

    public function initFormat(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        if (!$command instanceof LintCommand) {
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

    public function afterChecking(AfterCheckingEvent $event): void
    {
        $outputHandler = new ChainOutput($this->handlers);
        $outputHandler->format($event->getArgument('results'));
    }
}
