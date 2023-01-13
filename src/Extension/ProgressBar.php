<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Extension;

use Overtrue\PHPLint\Console\Style;
use Overtrue\PHPLint\Event\AfterCheckingEvent;
use Overtrue\PHPLint\Event\AfterCheckingInterface;
use Overtrue\PHPLint\Event\AfterLintFileEvent;
use Overtrue\PHPLint\Event\AfterLintFileInterface;
use Overtrue\PHPLint\Event\BeforeCheckingEvent;
use Overtrue\PHPLint\Event\BeforeCheckingInterface;
use Overtrue\PHPLint\Event\BeforeLintFileEvent;
use Overtrue\PHPLint\Event\BeforeLintFileInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Laurent Laville
 * @since Release 7.0.0
 */
final class ProgressBar implements
    EventSubscriberInterface,
    BeforeCheckingInterface,
    AfterCheckingInterface,
    BeforeLintFileInterface,
    AfterLintFileInterface
{
    private bool $enabled = false;
    private StyleInterface $io;

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => 'initProgress',
        ];
    }

    /**
     * Initialize progress bar extension
     */
    public function initProgress(ConsoleCommandEvent $event): void
    {
        $input = $event->getInput();
        $output = $event->getOutput();
        $this->io = new Style($input, $output);

        if ($input->hasOption('progress') && $input->getOption('progress') == 'bar') {
            $this->enabled = !$input->getOption('no-progress');
        }
    }

    public function beforeChecking(BeforeCheckingEvent $event): void
    {
        if ($this->enabled) {
            $this->io->headerBlock($event->getArgument('appVersion'), $event->getArgument('options'));
            $this->io->progressStart($event->getArgument('fileCount'));
        }
    }

    public function afterChecking(AfterCheckingEvent $event): void
    {
        if ($this->enabled) {
            $this->io->progressFinish();
        }
    }

    public function beforeLintFile(BeforeLintFileEvent $event): void
    {
        if ($this->enabled) {
            $this->io->progressMessage(sprintf('Checking file %s ...', $event->getArgument('file')->getPathname()));
        }
    }

    public function afterLintFile(AfterLintFileEvent $event): void
    {
        if ($this->enabled) {
            $this->io->progressAdvance();
        }
    }
}
