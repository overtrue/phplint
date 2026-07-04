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

namespace Overtrue\PHPLint\Output;

use PHP_Parallel_Lint\PhpConsoleColor\ConsoleColor;
use PHP_Parallel_Lint\PhpConsoleColor\InvalidStyleException;
use PHP_Parallel_Lint\PhpConsoleHighlighter\Highlighter;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput as SymfonyConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

use function abs;
use function array_filter;
use function array_slice;
use function class_exists;
use function count;
use function end;
use function file;
use function file_get_contents;
use function in_array;
use function json_encode;
use function key;
use function max;
use function realpath;
use function rtrim;
use function str_pad;
use function str_repeat;
use function strlen;

use const ARRAY_FILTER_USE_KEY;
use const PHP_EOL;
use const STR_PAD_LEFT;

/**
 * @author Laurent Laville
 * @since Release 9.0.0
 */
final class ConsoleOutput extends SymfonyConsoleOutput implements ConsoleOutputInterface, OutputInterface
{
    public function getName(): string
    {
        return 'console';
    }

    public function format(LinterOutput $results): void
    {
        $data = $results->getFailures();
        $context = $results->getContext();

        $errCount = count($data);

        if ($context['files_count'] === 0) {
            $this->warningBlock();
            return;
        }

        $options = $context['options_used'] ?? [];
        $configFile = $options['no-configuration'] ? '' : $options['configuration'];
        $this->headerBlock($context['application_version']['long'], $configFile);

        $this->consumeBlock($context['time_usage'], $context['memory_usage'], $context['cache_usage'], $context['process_count']);

        if ($errCount > 0) {
            $this->errorBlock($context['files_count'], $errCount);
            try {
                $this->showErrors($data);
            } catch (InvalidStyleException) {
            }
        } else {
            $this->successBlock($context['files_count']);
        }
    }

    public function headerBlock(string $appVersion, string $configFile): void
    {
        $this->newLine();
        $this->writeln($appVersion);
        $this->newLine();

        $this->writeln(sprintf('Runtime       : PHP <comment>%s</comment>', phpversion()));

        $this->writeln(sprintf(
            'Configuration : <comment>%s</comment>',
            (!realpath($configFile) || empty($configFile)) ? 'No config file loaded' : realpath($configFile)
        ));

        $this->newLine();
    }

    public function consumeBlock(string $timeUsage, string $memUsage, string $cacheUsage, int $processCount): void
    {
        $message = sprintf(
            'Time: <info>%s</info>, Memory: <info>%s</info>, Cache: <info>%s</info>, Process%s: <info>%s</info>',
            $timeUsage,
            $memUsage,
            $cacheUsage,
            $processCount > 1 ? 'es' : '',
            $processCount
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

        $style = new SymfonyStyle(new ArrayInput([]), $this);
        $style->error($message);
    }

    public function successBlock(int $fileCount): void
    {
        $message = sprintf(
            '%d file%s',
            $fileCount,
            $fileCount > 1 ? 's' : ''
        );

        $style = new SymfonyStyle(new ArrayInput([]), $this);
        $style->success($message);
    }

    public function warningBlock(string $message = self::NO_FILE_TO_LINT): void
    {
        $style = new SymfonyStyle(new ArrayInput([]), $this);
        $style->warning($message);
    }

    public function newLine(int $count = 1): void
    {
        $this->write(str_repeat(PHP_EOL, $count));
    }

    /**
     * @throws InvalidStyleException
     */
    private function showErrors(array $errors): void
    {
        $i = 0;
        $this->writeln(PHP_EOL . "There was " . count($errors) . ' errors:');
        foreach ($errors as $filename => $error) {
            $this->writeln('<comment>' . ++$i . ". {$filename}:{$error['line']}" . '</comment>');
            $this->writeln($this->getHighlightedCodeSnippet($filename, $error['line']));
            $this->writeln("<error> {$error['error']}</error>");
        }
    }

    private function getCodeSnippet(string $filePath, int $lineNumber, int $linesBefore = 3, int $linesAfter = 3): string
    {
        $lines = file($filePath);
        $offset = $lineNumber - $linesBefore - 1;
        $offset = max($offset, 0);
        $length = $linesAfter + $linesBefore + 1;
        $lines = array_slice($lines, $offset, $length, true);
        end($lines);
        $lineLength = strlen((string) (key($lines) + 1));
        $snippet = '';

        foreach ($lines as $i => $line) {
            $snippet .= abs($lineNumber) === $i + 1 ? '  > ' : '    ';
            $snippet .= str_pad((string) ($i + 1), $lineLength, ' ', STR_PAD_LEFT) . '| ' . rtrim($line) . PHP_EOL;
        }

        return $snippet;
    }

    /**
     * @throws InvalidStyleException
     */
    private function getHighlightedCodeSnippet(string $filePath, int $lineNumber, int $linesBefore = 3, int $linesAfter = 3): string
    {
        if (
            !$this->isDecorated() ||
            !class_exists('\PHP_Parallel_Lint\PhpConsoleHighlighter\Highlighter') ||
            !class_exists('\PHP_Parallel_Lint\PhpConsoleColor\ConsoleColor')
        ) {
            return $this->getCodeSnippet($filePath, $lineNumber, $linesBefore, $linesAfter);
        }

        $colors = new ConsoleColor();
        $highlighter = new Highlighter($colors);
        $fileContent = file_get_contents($filePath);

        return $highlighter->getCodeSnippet($fileContent, $lineNumber, $linesBefore, $linesAfter);
    }
}
