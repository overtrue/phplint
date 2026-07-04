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
use Overtrue\PHPLint\Helper\ProgressHelper;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Laurent Laville
 * @since Release 9.0.0
 */
final class ProgressPrinter implements
    ExtensionEventInterface,
    BeforeCheckingInterface,
    AfterLintFileInterface
{
    private OutputInterface $output;

    private int $maxSteps = 0;
    private bool $hasProcessHelper;
    private ProgressHelper|HelperInterface $progressHelper;

    /**
     * Initializes the progress dots widget (default legacy behavior)
     */
    public function initialize(ConsoleCommandEvent $event): void
    {
        $this->hasProcessHelper = $event->getCommand()->getHelperSet()->has('process');
        $this->progressHelper = $event->getCommand()->getHelperSet()->get('progress');
        $this->output = $event->getOutput();
    }

    /**
     * Finishes the progress dots widget
     */
    public function finish(AfterCheckingEvent $event): void
    {
        $this->output->writeln('');
    }

    public function beforeChecking(BeforeCheckingEvent $event): void
    {
        $this->maxSteps = $event->getArgument($event::FILE_COUNT);
    }

    public function afterLintFile(AfterLintFileEvent $event): void
    {
        if ($this->hasProcessHelper && $this->output->getVerbosity() === OutputInterface::VERBOSITY_VERY_VERBOSE) {
            // ProgressPrinter extension make some noise that break output when ProcessHelper is active in verbose level 2
            return;
        }

        $this->progressHelper->progressPrinterAdvance(
            $this->maxSteps,
            $event->getArgument($event::FILE_STATUS),
            $event->getArgument($event::FILE_INFO),
            1,
            $this->output
        );
    }
}
