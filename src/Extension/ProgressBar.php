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

use LogicException;
use Overtrue\PHPLint\Event\AfterCheckingEvent;
use Overtrue\PHPLint\Event\AfterLintFileEvent;
use Overtrue\PHPLint\Event\AfterLintFileInterface;
use Overtrue\PHPLint\Event\BeforeCheckingEvent;
use Overtrue\PHPLint\Event\BeforeCheckingInterface;
use Overtrue\PHPLint\Event\BeforeLintFileEvent;
use Overtrue\PHPLint\Event\BeforeLintFileInterface;
use Overtrue\PHPLint\Output\ConsoleOutputInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use function get_class;
use function mb_strimwidth;
use function min;
use function sprintf;
use function strlen;

/**
 * @author Laurent Laville
 * @since Release 9.0.0
 */
final class ProgressBar implements
    ExtensionEventInterface,
    EventSubscriberInterface,
    BeforeCheckingInterface,
    BeforeLintFileInterface,
    AfterLintFileInterface
{
    private ConsoleOutputInterface $output;
    private bool $hasProcessHelper;

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => 'initialize',
            AfterCheckingEvent::class => 'finish',
            BeforeCheckingEvent::class => 'beforeChecking',
            BeforeLintFileEvent::class => 'beforeLintFile',
            AfterLintFileEvent::class => 'afterLintFile',
        ];
    }

    /**
     * Initializes the progress bar widget
     */
    public function initialize(ConsoleCommandEvent $event): void
    {
        $this->hasProcessHelper = $event->getCommand()->getHelperSet()->has('process');

        $output = $event->getOutput();

        if (!$output instanceof ConsoleOutputInterface) {
            throw new LogicException(
                sprintf(
                    'Extension %s must implement %s',
                    get_class($this),
                    ConsoleOutputInterface::class
                )
            );
        }

        $this->output = $output;
    }

    /**
     * Finishes the progress bar widget
     */
    public function finish(AfterCheckingEvent $event): void
    {
        $this->output->progressFinish();
    }

    public function beforeChecking(BeforeCheckingEvent $event): void
    {
        if ($this->hasProcessHelper && $this->output->isVeryVerbose()) {
            // ProgressBar extension make some noise that break output when ProcessHelper is active
            return;
        }
        $this->output->progressStart($event->getArgument('fileCount'));
    }

    public function beforeLintFile(BeforeLintFileEvent $event): void
    {
        $this->output->progressMessage('Checking file ...');

        $filename = $event->getArgument('file')->getRelativePathname();
        $width = min(strlen($filename), 70);
        $this->output->progressMessage(mb_strimwidth($filename, -$width, $width), 'filename');
    }

    public function afterLintFile(AfterLintFileEvent $event): void
    {
        if ($this->hasProcessHelper && $this->output->isVeryVerbose()) {
            // ProgressBar extension make some noise that break output when ProcessHelper is active
            return;
        }

        $this->output->progressAdvance();
    }
}
