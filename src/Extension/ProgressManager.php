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
use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Console\ApplicationInterface;
use Overtrue\PHPLint\Event\AfterCheckingEvent;
use Overtrue\PHPLint\Event\AfterLintFileEvent;
use Overtrue\PHPLint\Event\AfterLintFileInterface;
use Overtrue\PHPLint\Event\BeforeCheckingEvent;
use Overtrue\PHPLint\Event\BeforeCheckingInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use function method_exists;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
final class ProgressManager implements
    ExtensionInterface,
    EventSubscriberInterface,
    BeforeCheckingInterface,
    AfterLintFileInterface
{
    private ?ExtensionEventInterface $widget = null;

    public function getName(): string
    {
        return 'progress';
    }

    public static function getCommands(): array
    {
        return [];
    }

    public static function getDefinition(): InputDefinition
    {
        return new InputDefinition([
            new InputOption(
                'progress',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Set type of progress output' .
                ' (<info>auto, quiet, plain, dots</info>)',
                OptionDefinition::DEFAULT_PROGRESS_WIDGET
            ),
            new InputOption(
                'no-progress',
                null,
                InputOption::VALUE_NONE,
                'Suppress the progress output (same as <comment>--progress quiet</comment>)'
            )
        ]);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => 'initialize',
            AfterCheckingEvent::class => 'finish',
            BeforeCheckingEvent::class => 'beforeChecking',
            AfterLintFileEvent::class => 'afterLintFile',
        ];
    }

    /**
     * Initializes the progress output.
     */
    public function initialize(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        if (!$command instanceof LintCommand) {
            // this extension must be only available for lint command
            return;
        }

        $input = $event->getInput();
        $output = $event->getOutput();

        $progress = 'dots';

        if (true === $input->hasParameterOption(['--no-progress'], true)
            || $output->isQuiet()
        ) {
            $progress = 'quiet';
        }

        if ($output->isVeryVerbose()) {
            $progress = 'plain';
        }

        if (true === $input->hasParameterOption(['--progress'], true)) {
            $progress = $input->getParameterOption('--progress');
        }
        if (true === $input->hasParameterOption(['-p'], true)) {
            $progress = $input->getParameterOption('-p');
        }
        $progress ??= OptionDefinition::DEFAULT_PROGRESS_WIDGET;

        $newEvent = clone $event;

        if ($progress === 'plain') {
            $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
            $newEvent = new ConsoleCommandEvent(
                $command,
                $input,
                $output
            );
        }

        $this->widget = match ($progress) {
            'bar' => new ProgressBar(),
            'indicator' => new ProgressIndicator(),
            'auto', 'dots', 'plain', 'printer' => new ProgressPrinter(),
            default => null,
        };

        if (!$this->widget instanceof EventSubscriberInterface) {
            return;
        }

        /**
         * @var ApplicationInterface|null $application
         * @phpstan-ignore varTag.nativeType
         */
        $application = $command->getApplication();

        $eventDispatcher = $application->getEventDispatcher();
        $eventDispatcher->addSubscriber($this->widget);
        $this->widget->initialize($newEvent);
    }

    /**
     * Finishes the progress output.
     */
    public function finish(AfterCheckingEvent $event): void
    {
        $this->widget?->finish($event);
    }

    public function beforeChecking(BeforeCheckingEvent $event): void
    {
        if (null === $this->widget || !method_exists($this->widget, __FUNCTION__)) {
            return;
        }
        $this->widget->{__FUNCTION__}($event);
    }

    public function afterLintFile(AfterLintFileEvent $event): void
    {
        if (null === $this->widget || !method_exists($this->widget, __FUNCTION__)) {
            return;
        }
        $this->widget->{__FUNCTION__}($event);
    }
}
