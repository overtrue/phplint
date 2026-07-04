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
use Overtrue\PHPLint\Event\AfterLintFileEvent;
use Overtrue\PHPLint\Event\AfterLintFileInterface;
use Overtrue\PHPLint\Event\BeforeCheckingEvent;
use Overtrue\PHPLint\Event\BeforeCheckingInterface;
use Overtrue\PHPLint\Event\BeforeLintFileEvent;
use Overtrue\PHPLint\Event\BeforeLintFileInterface;
use Overtrue\PHPLint\Helper\ProgressHelper;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function mb_strimwidth;
use function min;
use function strlen;

/**
 * @author Laurent Laville
 * @since Release 9.0.0
 */
final class ProgressBar implements
    ExtensionEventInterface,
    BeforeCheckingInterface,
    BeforeLintFileInterface,
    AfterLintFileInterface
{
    private OutputInterface $output;
    private bool $hasProcessHelper;
    private ProgressHelper|HelperInterface $progressHelper;

    /**
     * Initializes the progress bar widget
     */
    public function initialize(ConsoleCommandEvent $event): void
    {
        $this->hasProcessHelper = $event->getCommand()->getHelperSet()->has('process');
        $this->progressHelper = $event->getCommand()->getHelperSet()->get('progress');
        $this->output = $event->getOutput();
    }

    /**
     * Finishes the progress bar widget
     */
    public function finish(AfterCheckingEvent $event): void
    {
        $this->progressHelper->progressFinish();
    }

    public function beforeChecking(BeforeCheckingEvent $event): void
    {
        if ($this->hasProcessHelper && $this->output->isVeryVerbose()) {
            // ProgressBar extension make some noise that break output when ProcessHelper is active
            return;
        }

        $this->progressHelper->progressStart($event->getArgument($event::FILE_COUNT));
    }

    public function beforeLintFile(BeforeLintFileEvent $event): void
    {
        $this->progressHelper->progressMessage('Checking file ...');

        $filename = $event->getArgument($event::FILE_INFO)->getRelativePathname();
        $width = min(strlen($filename), 70);
        $this->progressHelper->progressMessage(mb_strimwidth($filename, -$width, $width), 'filename');
    }

    public function afterLintFile(AfterLintFileEvent $event): void
    {
        if ($this->hasProcessHelper && $this->output->isVeryVerbose()) {
            // ProgressBar extension make some noise that break output when ProcessHelper is active
            return;
        }

        $this->progressHelper->progressAdvance();
    }
}
