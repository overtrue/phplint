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
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Helper\ProgressIndicator as ProgressIndicatorHelper;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @link https://symfony.com/doc/current/components/console/helpers/progressindicator.html
 *
 * @author Laurent Laville
 * @since Release 9.1.0
 */
final class ProgressIndicator implements
    ExtensionEventInterface,
    BeforeCheckingInterface,
    AfterLintFileInterface
{
    private OutputInterface $output;
    private bool $hasProcessHelper;

    private ?ProgressIndicatorHelper $progressIndicator;

    /**
     * Initializes the progress indicator widget
     */
    public function initialize(ConsoleCommandEvent $event): void
    {
        $this->hasProcessHelper = $event->getCommand()->getHelperSet()->has('process');

        $this->output = $event->getOutput();

        if ($this->hasProcessHelper && $this->output->isVeryVerbose()) {
            $this->progressIndicator = null;
        } else {
            $this->progressIndicator = new ProgressIndicatorHelper(
                $this->output,
                'normal',
                100,
                ['⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇']
            );
        }
    }

    /**
     * Finishes the progress indicator widget
     */
    public function finish(AfterCheckingEvent $event): void
    {
        $this->progressIndicator?->finish('Finished');
    }

    public function beforeChecking(BeforeCheckingEvent $event): void
    {
        if ($this->hasProcessHelper && $this->output->isVeryVerbose()) {
            // ProgressIndicator extension make some noise that break output when ProcessHelper is active
            return;
        }

        $this->progressIndicator?->start('Linting files ...');
    }

    public function afterLintFile(AfterLintFileEvent $event): void
    {
        if ($this->hasProcessHelper && $this->output->isVeryVerbose()) {
            // ProgressIndicator extension make some noise that break output when ProcessHelper is active
            return;
        }

        $this->progressIndicator?->advance();
    }
}
