<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Console;

use Overtrue\PHPLint\Configuration\ConfigResolver;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Terminal;
use Symfony\Component\Finder\SplFileInfo;

use function floor;
use function json_encode;
use function phpversion;
use function sprintf;
use function str_pad;
use function strlen;

use const JSON_UNESCAPED_SLASHES;

/**
 * @author Laurent Laville
 * @since Release 7.0.0
 */
final class Style extends SymfonyStyle
{
    public const MAX_LINE_LENGTH = 110;

    private int $lineLength;
    private ?ProgressBar $progressBar = null;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        parent::__construct($input, $output);

        $width = (new Terminal())->getWidth() ?: self::MAX_LINE_LENGTH;
        $this->lineLength = min($width - (int) (DIRECTORY_SEPARATOR === '\\'), self::MAX_LINE_LENGTH);
    }

    public function createProgressBar($max = 0): ProgressBar
    {
        $progressBar = parent::createProgressBar($max);

        $formats = [
            'very_verbose' => ' %current%/%max% %percent:3s%% %elapsed:6s% %message%',
            'very_verbose_nomax' => ' %current% %elapsed:6s% %message%',

            'debug' => ' %current%/%max% %percent:3s%% %elapsed:6s% %memory:6s% %message%',
            'debug_nomax' => ' %current% %elapsed:6s% %memory:6s% %message%',
        ];
        foreach ($formats as $name => $format) {
            $progressBar::setFormatDefinition($name, $format);
        }

        $progressBar->setMessage('');
        $this->progressBar = $progressBar;
        return $progressBar;
    }

    public function progressFinish(): void
    {
        $this->progressBar?->finish();
        $this->progressBar?->clear();
        $this->newLine();
        unset($this->progressBar);
    }

    public function progressMessage(string $message)
    {
        $this->progressBar?->setMessage($message);
    }

    public function progressPrinterAdvance(int $maxSteps, string $status, SplFileInfo $fileInfo): void
    {
        static $i = 1;

        $percent = floor(($i / $maxSteps) * 100);
        $maxStepsLen = strlen((string) $maxSteps);
        $process = sprintf('%' . $maxStepsLen . 'd / %' . $maxStepsLen . 'd (%3s%%)', $i, $maxSteps, $percent);

        $maxColumn = $this->lineLength - 2 - strlen('[ XX ]') - strlen(' / (XXX%)') - (2 * $maxStepsLen);

        $withColor = function (string $color, string $indicator) {
            return sprintf('<%s>%s</>', $color, $indicator);
        };

        if ($this->isDebug()) {
            $filename = str_pad($fileInfo->getRelativePathname(), $maxColumn);

            if ($status === 'ok') {
                $st = $withColor('fg=green', ' OK ');
            } elseif ($status === 'error') {
                $st = $withColor('bg=red;fg=white', 'ERR ');
            } else {
                $st = $withColor('fg=yellow', 'WARN');
            }

            $this->writeln(sprintf("[ %s ] %s %" . strlen($process) . "s", $st, $filename, $process));
        } else {
            if ($i && 0 === $i % $maxColumn) {
                $this->writeln($process);
            }

            if ($status === 'ok') {
                $this->write($withColor('fg=green', '.'));
            } elseif ($status === 'error') {
                $this->write($withColor('bg=red;fg=white', 'E'));
            } else {
                $this->write($withColor('fg=yellow', 'W'));
            }

            if ($i == $maxSteps) {
                $this->newLine();
            }
        }
        ++$i;
    }

    public function headerBlock(string $appVersion, array $options): void
    {
        $this->writeln($appVersion . " by overtrue and contributors.");
        $this->newLine();

        $this->writeln(sprintf('Runtime       : PHP <comment>%s</comment>', phpversion()));

        $configuration = empty($options[ConfigResolver::OPTION_CONFIG_FILE])
            ? 'No config file loaded'
            : $options[ConfigResolver::OPTION_CONFIG_FILE];
        $this->writeln(sprintf('Configuration : <comment>%s</comment>', $configuration));

        if ($this->isDebug()) {
            $this->writeln('Options       :');
            foreach ($options as $name => $value) {
                $this->writeln(
                    sprintf(
                        '<comment>%18s</comment> > <info>%s</info>',
                        $name,
                        json_encode($value, JSON_UNESCAPED_SLASHES)
                    )
                );
            }
        }
        $this->newLine();
    }

    public function consumeBlock(string $timeUsage, string $memUsage, string $cacheUsage): void
    {
        $message = sprintf(
            'Time: <info>%s</info>, Memory: <info>%s</info>, Cache: <info>%s</info>',
            $timeUsage,
            $memUsage,
            $cacheUsage
        );
        $this->newLine();
        $this->writeln($message);
    }

    public function errorBlock(int $fileCount, int $errorCount): void
    {
        $message = sprintf(
            '%d file%s, %d error%s',
            $fileCount,
            $fileCount > 1 ? 's' : '',
            $errorCount,
            $errorCount > 1 ? 's' : ''
        );
        $this->error($message);
    }

    public function successBlock(int $fileCount): void
    {
        $message = sprintf(
            '%d file%s',
            $fileCount,
            $fileCount > 1 ? 's' : ''
        );
        $this->success($message);
    }

    public function warningBlock(string $message = 'Could not find files to lint'): void
    {
        $this->warning($message);
    }
}
