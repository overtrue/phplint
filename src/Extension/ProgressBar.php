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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use function mb_strimwidth;
use function min;
use function strlen;

/**
 * @author Laurent Laville
 * @since Release 9.0.0
 */
final class ProgressBar implements
    EventSubscriberInterface,
    BeforeCheckingInterface,
    AfterCheckingInterface,
    BeforeLintFileInterface,
    AfterLintFileInterface
{
    private OutputInterface $output;

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
        $this->output->progressStart($event->getArgument('fileCount'));
    }

    public function afterChecking(AfterCheckingEvent $event): void
    {
        $this->output->progressFinish();
    }

    public function beforeLintFile(BeforeLintFileEvent $event): void
    {
        $this->output->progressMessage('Checking file ...');

        $filename = $event->getArgument('file')->getRelativePathname();
        $width = min(strlen($filename), 70);
        $this->output->progressMessage(mb_strimwidth($filename, -1 * $width, $width), 'filename');
    }

    public function afterLintFile(AfterLintFileEvent $event): void
    {
        $this->output->progressAdvance();
    }
}
