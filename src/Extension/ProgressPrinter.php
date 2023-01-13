<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Extension;

use Overtrue\PHPLint\Console\Style;
use Overtrue\PHPLint\Event\AfterLintFileEvent;
use Overtrue\PHPLint\Event\AfterLintFileInterface;
use Overtrue\PHPLint\Event\BeforeCheckingEvent;
use Overtrue\PHPLint\Event\BeforeCheckingInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Laurent Laville
 * @since Release 7.0.0
 */
final class ProgressPrinter implements
    EventSubscriberInterface,
    BeforeCheckingInterface,
    AfterLintFileInterface
{
    private bool $enabled = false;
    private StyleInterface $io;
    private int $maxSteps = 0;

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => 'initProgress',
        ];
    }

    /**
     * Initialize progress printer extension (default legacy behavior)
     */
    public function initProgress(ConsoleCommandEvent $event): void
    {
        $input = $event->getInput();
        $output = $event->getOutput();
        $this->io = new Style($input, $output);

        if ($input->hasOption('progress') && $input->getOption('progress') == 'printer') {
            $this->enabled = !$input->getOption('no-progress');
        }
    }

    public function beforeChecking(BeforeCheckingEvent $event): void
    {
        if ($this->enabled) {
            $this->io->headerBlock($event->getArgument('appVersion'), $event->getArgument('options'));
            $this->maxSteps = $event->getArgument('fileCount');
        }
    }

    public function afterLintFile(AfterLintFileEvent $event): void
    {
        if ($this->enabled) {
            $this->io->progressPrinterAdvance(
                $this->maxSteps,
                $event->getArgument('status'),
                $event->getArgument('file')
            );
        }
    }
}
