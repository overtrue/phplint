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

use Overtrue\PHPLint\Event\AfterLintFileEvent;
use Overtrue\PHPLint\Event\AfterLintFileInterface;
use Overtrue\PHPLint\Event\BeforeCheckingEvent;
use Overtrue\PHPLint\Event\BeforeCheckingInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Laurent Laville
 * @since Release 9.0.0
 */
final class ProgressPrinter implements
    EventSubscriberInterface,
    BeforeCheckingInterface,
    AfterLintFileInterface
{
    private OutputInterface $output;

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
        $this->output = $event->getOutput();
    }

    public function beforeChecking(BeforeCheckingEvent $event): void
    {
        $configFile = $event->getArgument('options')['no-configuration']
            ? ''
            : $event->getArgument('options')['configuration']
        ;

        $this->output->headerBlock($event->getArgument('appVersion'), $configFile);
        $this->output->configBlock($event->getArgument('options'));

        $this->maxSteps = $event->getArgument('fileCount');
    }

    public function afterLintFile(AfterLintFileEvent $event): void
    {
        $this->output->progressPrinterAdvance(
            $this->maxSteps,
            $event->getArgument('status'),
            $event->getArgument('file')
        );
    }
}
