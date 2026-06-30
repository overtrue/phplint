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

namespace Overtrue\PHPLint\Helper;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;
use Symfony\Component\Finder\SplFileInfo;

use function getenv;
use function mb_strimwidth;
use function min;
use function str_pad;
use function str_repeat;
use function strlen;

use const DIRECTORY_SEPARATOR;
use const PHP_EOL;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
final class ProgressHelper extends Helper
{
    private ?ProgressBar $progressBar = null;

    public function __construct(private readonly OutputInterface $output, private ?Terminal $terminal = null)
    {
        $this->terminal ??= new Terminal();
    }

    public function getName(): string
    {
        return 'progress';
    }

    public function createProgressBar($max = 0): ProgressBar
    {
        $progressBar = new ProgressBar($this->output, $max);
        if ('\\' !== DIRECTORY_SEPARATOR || 'Hyper' === getenv('TERM_PROGRAM')) {
            $progressBar->setEmptyBarCharacter('░'); // light shade character \u2591
            $progressBar->setProgressCharacter('');
            $progressBar->setBarCharacter('▓'); // dark shade character \u2593
        }

        $formats = [
            'very_verbose' => ' %current%/%max% %percent:3s%% %elapsed:6s% %message% %filename%',
            'very_verbose_nomax' => ' %current% %elapsed:6s% %message% %filename%',

            'debug' => ' %current%/%max% %percent:3s%% %elapsed:6s% %memory:6s% %message% %filename%',
            'debug_nomax' => ' %current% %elapsed:6s% %memory:6s% %message% %filename%',
        ];
        foreach ($formats as $name => $format) {
            $progressBar::setFormatDefinition($name, $format);
        }

        $progressBar->setMessage('Checking ...');
        $progressBar->setMessage('', 'filename');
        $this->progressBar = $progressBar;
        return $progressBar;
    }

    public function progressStart(int $max = 0): void
    {
        $this->progressBar = $this->createProgressBar($max);
        $this->progressBar->start();
    }

    public function progressAdvance(int $step = 1): void
    {
        $this->progressBar?->advance($step);
    }

    public function progressFinish(): void
    {
        $this->progressBar?->finish();
        $this->progressBar?->clear();
        unset($this->progressBar);
    }

    public function progressMessage(string $message, string $name = 'message'): void
    {
        $this->progressBar?->setMessage($message, $name);
    }

    public function progressPrinterAdvance(int $maxSteps, string $status, SplFileInfo $fileInfo, int $step, OutputInterface $output): void
    {
        static $i = 1;

        $percent = floor(($i / $maxSteps) * 100);
        $maxStepsLen = strlen((string) $maxSteps);
        $process = sprintf('%' . $maxStepsLen . 'd / %' . $maxStepsLen . 'd (%3s%%)', $i, $maxSteps, $percent);

        $maxColumn = $this->terminal->getWidth() - 2 - strlen('[ XX ]') - strlen(' / (XXX%)') - (2 * $maxStepsLen);

        $withColor = static fn (string $color, string $indicator) => sprintf('<%s>%s</>', $color, $indicator);

        if ($output->isDebug()) {
            $filename = $fileInfo->getRelativePathname();
            $width = min(strlen($filename), $maxColumn);
            $filename = str_pad(mb_strimwidth($filename, -$width, $width), $maxColumn);

            if ($status === 'ok') {
                $st = $withColor('fg=green', ' OK ');
            } elseif ($status === 'error') {
                $st = $withColor('bg=red;fg=white', 'ERR ');
            } else {
                $st = $withColor('fg=yellow', 'WARN');
            }

            $output->writeln(sprintf("[ %s ] %s %" . strlen($process) . "s", $st, $filename, $process));
        } else {
            if ($i && 0 === $i % $maxColumn) {
                $output->writeln($process);
            }

            if ($status === 'ok') {
                $output->write($withColor('fg=green', '.'));
            } elseif ($status === 'error') {
                $output->write($withColor('bg=red;fg=white', 'E'));
            } else {
                $output->write($withColor('fg=yellow', 'W'));
            }

            if ($i === $maxSteps) {
                $output->write(str_repeat(PHP_EOL, 1));
            }
        }

        $i += $step;
    }
}
