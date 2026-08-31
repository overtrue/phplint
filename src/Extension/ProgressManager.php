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

use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Event\AfterCheckingEvent;
use Overtrue\PHPLint\Event\AfterLintFileEvent;
use Overtrue\PHPLint\Event\AfterLintFileInterface;
use Overtrue\PHPLint\Event\BeforeCheckingEvent;
use Overtrue\PHPLint\Event\BeforeCheckingInterface;
use Overtrue\PHPLint\Event\Events;
use Overtrue\PHPLint\Helper\DebugFormatterHelper;
use Overtrue\PHPLint\Helper\ProcessHelper;
use Overtrue\PHPLint\Helper\ProgressHelper;
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
final class ProgressManager extends AbstractManager implements
    ExtensionInterface,
    EventSubscriberInterface,
    BeforeCheckingInterface,
    AfterLintFileInterface
{
    private ?ExtensionEventInterface $widget = null;

    public function getName(): string
    {
        return ExtensionEnum::PROGRESS_MANAGER->value;
    }

    public static function getDefinition(): InputDefinition
    {
        return new InputDefinition([
            new InputOption(
                OptionDefinition::PROGRESS,
                'p',
                InputOption::VALUE_OPTIONAL,
                'Set type of progress output' .
                ' (<info>auto, quiet, plain, dots</info>)',
                OptionDefinition::DEFAULT_PROGRESS_WIDGET
            ),
            new InputOption(
                OptionDefinition::NO_PROGRESS,
                null,
                InputOption::VALUE_NONE,
                'Suppress the progress output (<comment>Deprecated option, use "--progress quiet" instead</comment>)'
            )
        ]);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => 'initialize',
            Events::AFTER_CHECKING => 'finish',
            Events::BEFORE_CHECKING => 'beforeChecking',
            Events::AFTER_LINT_FILE => 'afterLintFile',
        ];
    }

    /**
     * Initializes the progress output.
     */
    public function initialize(ConsoleCommandEvent $event): void
    {
        $this->describeEvent($event);

        $command = $event->getCommand();
        if ($command->getName() !== 'lint') {
            // this extension must be only available for lint command
            return;
        }

        $output = $event->getOutput();

        $helperSet = $command->getHelperSet();
        $helperSet?->set(new ProgressHelper($output));

        $input = $event->getInput();

        $progress = ProgressEnum::DOTS->value;

        if (true === $input->getOption(OptionDefinition::NO_PROGRESS)
            || $output->isQuiet()
        ) {
            $progress = ProgressEnum::QUIET->value;
        }

        if ($output->isVeryVerbose()) {
            $progress = ProgressEnum::PLAIN->value;
        }

        if (true === $input->hasParameterOption(['--' . OptionDefinition::PROGRESS, '-p'], true)) {
            $progress = $input->getParameterOption(['--' . OptionDefinition::PROGRESS, '-p']);
        }

        $progress ??= OptionDefinition::DEFAULT_PROGRESS_WIDGET;

        $newEvent = clone $event;

        if ($progress === ProgressEnum::PLAIN->value) {
            $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
            $newEvent = new ConsoleCommandEvent(
                $command,
                $input,
                $output
            );
        }

        $this->widget = match ($progress) {
            ProgressEnum::BAR->value => new ProgressBar(),
            ProgressEnum::INDICATOR->value => new ProgressIndicator(),
            ProgressEnum::AUTO->value, ProgressEnum::DOTS->value, ProgressEnum::PLAIN->value, 'printer' => new ProgressPrinter(),
            ProgressEnum::NEVER->value, ProgressEnum::QUIET->value => null,
            default => throw new \InvalidArgumentException(\sprintf('Unknown progress enum case "%s"', $progress)),
        };

        if ($this->widget instanceof ExtensionEventInterface) {
            if ($this->widget instanceof ProgressPrinter) {
                // these helpers are only necessary when using the `--progress plain` flag
                $helperSet?->set(new ProcessHelper());
                $helperSet?->set(new DebugFormatterHelper());
            }
            $this->widget->initialize($newEvent);
        }
    }

    /**
     * Finishes the progress output.
     */
    public function finish(AfterCheckingEvent $event): void
    {
        $this->describeEvent($event);

        $this->widget?->finish($event);
    }

    public function beforeChecking(BeforeCheckingEvent $event): void
    {
        $this->describeEvent($event);

        if (null === $this->widget || !method_exists($this->widget, __FUNCTION__)) {
            return;
        }
        $this->widget->{__FUNCTION__}($event);
    }

    public function afterLintFile(AfterLintFileEvent $event): void
    {
        $this->describeEvent($event);

        if (null === $this->widget || !method_exists($this->widget, __FUNCTION__)) {
            return;
        }
        $this->widget->{__FUNCTION__}($event);
    }
}
