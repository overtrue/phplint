<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Extension\Reporter;

use Overtrue\PHPLint\Console\Style;
use Overtrue\PHPLint\Extension\Reporter;
use PHP_Parallel_Lint\PhpConsoleColor\ConsoleColor;
use PHP_Parallel_Lint\PhpConsoleColor\InvalidStyleException;
use PHP_Parallel_Lint\PhpConsoleHighlighter\Highlighter;

use function abs;
use function array_slice;
use function class_exists;
use function count;
use function end;
use function file;
use function file_get_contents;
use function key;
use function max;
use function rtrim;
use function str_pad;
use function strlen;

use const PHP_EOL;
use const STR_PAD_LEFT;

/**
 * @author Laurent Laville
 * @since Release 7.0.0
 */
final class ConsoleReporter extends Reporter
{
    public function format($data, string $filename): void
    {
        $io = new Style($this->input, $this->output);

        $errCount = count($data);

        if ($this->context['files_count'] === 0) {
            $io->warningBlock();
            return;
        }

        $io->consumeBlock($this->context['time_usage'], $this->context['memory_usage'], $this->context['cache_usage']);

        if ($errCount > 0) {
            $io->errorBlock($this->context['files_count'], $errCount);
            try {
                $this->showErrors($data);
            } catch (InvalidStyleException $e) {
            }
        } else {
            $io->successBlock($this->context['files_count']);
        }
    }

    /**
     * @throws InvalidStyleException
     */
    protected function showErrors(array $errors): void
    {
        $i = 0;
        $this->output->writeln("\nThere was " . count($errors) . ' errors:');

        foreach ($errors as $filename => $error) {
            $this->output->writeln('<comment>' . ++$i . ". $filename:{$error['line']}" . '</comment>');

            $this->output->writeln($this->getHighlightedCodeSnippet($filename, $error['line']));

            $this->output->writeln("<error> {$error['error']}</error>");
        }
    }

    protected function getCodeSnippet(string $filePath, int $lineNumber, int $linesBefore = 3, int $linesAfter = 3): string
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
            $snippet .= (abs($lineNumber) === $i + 1 ? '  > ' : '    ');
            $snippet .= str_pad((string) ($i + 1), $lineLength, ' ', STR_PAD_LEFT) . '| ' . rtrim($line) . PHP_EOL;
        }

        return $snippet;
    }

    /**
     * @throws InvalidStyleException
     */
    protected function getHighlightedCodeSnippet(string $filePath, int $lineNumber, int $linesBefore = 3, int $linesAfter = 3): string
    {
        if (
            !$this->input->getOption('ansi') ||
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
