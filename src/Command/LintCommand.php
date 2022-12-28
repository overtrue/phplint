<?php

namespace Overtrue\PHPLint\Command;

use DateTime;
use N98\JUnitXml\Document;
use Overtrue\PHPLint\Cache;
use Overtrue\PHPLint\Linter;
use PHP_Parallel_Lint\PhpConsoleColor\ConsoleColor;
use PHP_Parallel_Lint\PhpConsoleColor\InvalidStyleException;
use PHP_Parallel_Lint\PhpConsoleHighlighter\Highlighter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class LintCommand extends Command
{
    protected array $defaults = [
        'jobs' => 5,
        'path' => '.',
        'exclude' => [],
        'extensions' => ['php'],
        'warning' => false
    ];

    protected InputInterface $input;
    protected OutputInterface $output;

    protected function configure(): void
    {
        $this
            ->setName('phplint')
            ->setDescription('Lint something')
            ->addArgument(
                'path',
                InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                'Path to file or directory to lint.'
            )
            ->addOption(
                'exclude',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Path to file or directory to exclude from linting'
            )
            ->addOption(
                'extensions',
                null,
                InputOption::VALUE_REQUIRED,
                'Check only files with selected extensions (default: php)'
            )
            ->addOption(
                'jobs',
                'j',
                InputOption::VALUE_REQUIRED,
                'Number of parraled jobs to run (default: 5)'
            )
            ->addOption(
                'configuration',
                'c',
                InputOption::VALUE_REQUIRED,
                'Read configuration from config file (default: ./.phplint.yml).'
            )
            ->addOption(
                'no-configuration',
                null,
                InputOption::VALUE_NONE,
                'Ignore default configuration file (default: ./.phplint.yml).'
            )
            ->addOption(
                'no-cache',
                null,
                InputOption::VALUE_NONE,
                'Ignore cached data.'
            )
            ->addOption(
                'cache',
                null,
                InputOption::VALUE_REQUIRED,
                'Path to the cache file.'
            )
            ->addOption(
                'no-progress',
                null,
                InputOption::VALUE_NONE,
                'Hide the progress output.'
            )
            ->addOption(
                'json',
                null,
                InputOption::VALUE_OPTIONAL,
                'Path to store JSON results.'
            )
            ->addOption(
                'xml',
                null,
                InputOption::VALUE_OPTIONAL,
                'Path to store JUnit XML results.'
            )
            ->addOption(
                'warning',
                'w',
                InputOption::VALUE_NONE,
                'Also show warnings.'
            )
            ->addOption(
                'quiet',
                'q',
                InputOption::VALUE_NONE,
                'Allow to silently fail.'
            )
            ->addOption(
                'no-files-exit-code',
                null,
                InputOption::VALUE_NONE,
                'Throw error if no files processed.'
            );
    }

    public function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * @throws InvalidStyleException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $startTime = microtime(true);
        $startMemUsage = memory_get_usage(true);

        $output->writeln($this->getApplication()->getLongVersion() . " by overtrue and contributors.\n");

        $options = $this->mergeOptions();
        $verbosity = $output->getVerbosity();

        if ($verbosity >= OutputInterface::VERBOSITY_DEBUG) {
            $output->writeln('Options: ' . json_encode($options) . "\n");
        }

        $linter = new Linter($options['path'], $options['exclude'], $options['extensions'], $options['warning']);
        $linter->setProcessLimit($options['jobs']);

        if (!empty($options['cache'])) {
            Cache::setFilename($options['cache']);
        }

        $usingCache = 'No';
        if (!$input->getOption('no-cache') && Cache::isCached()) {
            $usingCache = 'Yes';
            $linter->setCache(Cache::get());
        }

        if (!empty($options['memory_limit'])) {
            $linter->setMemoryLimit($options['memory_limit']);
        }

        $fileCount = count($linter->getFiles());
        $code = 0;

        if ($fileCount <= 0) {
            $output->writeln('<info>Could not find files to lint</info>');

            if (!empty($options['no-files-exit-code'])) {
                $code = 1;
            }

            return $code;
        }

        $errors = $this->executeLint($linter, $input, $output, $fileCount);

        $timeUsage = Helper::formatTime(microtime(true) - $startTime);
        $memUsage = Helper::formatMemory(memory_get_usage(true) - $startMemUsage);

        $errCount = count($errors);

        $output->writeln(sprintf(
            "\n\nTime: <info>%s</info>\tMemory: <info>%s</info>\tCache: <info>%s</info>\n",
            $timeUsage,
            $memUsage,
            $usingCache
        ));

        if ($errCount > 0) {
            $output->writeln('<error>FAILURES!</error>');
            $output->writeln("<error>Files: {$fileCount}, Failures: {$errCount}</error>");
            $this->showErrors($errors);

            if (empty($options['quiet'])) {
                $code = 1;
            }
        } else {
            $output->writeln("<info>OK! (Files: {$fileCount}, Success: {$fileCount})</info>");
        }

        $context = [
            'time_usage' => $timeUsage,
            'memory_usage' => $memUsage,
            'using_cache' => 'Yes' == $usingCache,
            'files_count' => $fileCount,
        ];

        if (!empty($options['json'])) {
            $this->dumpJsonResult((string) $options['json'], $errors, $options, $context);
        }

        if (!empty($options['xml'])) {
            $this->dumpXmlResult((string) $options['xml'], $errors, $options, $context);
        }

        return $code;
    }

    protected function dumpJsonResult(string $path, array $errors, array $options, array $context = []): void
    {
        $result = [
            'status' => 'success',
            'options' => $options,
            'errors' => $errors,
        ];

        \file_put_contents($path, \json_encode(\array_merge($result, $context)));
    }

    protected function dumpXmlResult(string $path, array $errors, array $options, array $context = []): void
    {
        $document = new Document();
        $suite = $document->addTestSuite();
        $suite->setName('PHP Linter');
        $suite->setTimestamp(new DateTime());
        $suite->setTime($context['time_usage']);
        $testCase = $suite->addTestCase();

        foreach ($errors as $errorName => $value) {
            $testCase->addError($errorName, 'Error', $value['error']);
        }

        $document->save($path);
    }

    protected function executeLint(Linter $linter, InputInterface $input, OutputInterface $output, int $filesCount): array
    {
        $cache = !$input->getOption('no-cache');
        $maxColumns = floor((new Terminal())->getWidth() / 2);
        $verbosity = $output->getVerbosity();
        $displayProgress = !$input->getOption('no-progress');

        $displayProgress && $linter->setProcessCallback(function ($status, SplFileInfo $file) use ($output, $verbosity, $filesCount, $maxColumns) {
            static $i = 1;

            $percent = floor(($i / $filesCount) * 100);
            $process = str_pad(" {$i} / {$filesCount} ({$percent}%)", 18, ' ', STR_PAD_LEFT);

            if ($verbosity >= OutputInterface::VERBOSITY_VERBOSE) {
                $filename = str_pad(" {$i}: " . $file->getRelativePathname(), $maxColumns - 10);
                if ($status === 'ok') {
                    $status = '<info>OK</info>';
                } elseif ($status === 'error') {
                    $status = '<error>Error</error>';
                } else {
                    $status = '<error>Warning</error>';
                }

                $status = \str_pad($status, 20);
                $output->writeln(\sprintf("%s\t%s\t%s", $filename, $status, $process));
            } else {
                if ($i && 0 === $i % $maxColumns) {
                    $output->writeln($process);
                }
                if ($status === 'ok') {
                    $status = '<info>.</info>';
                } elseif ($status === 'error') {
                    $status = '<error>E</error>';
                } else {
                    $status = '<error>W</error>';
                }

                $output->write($status);
            }
            ++$i;
        });

        $displayProgress || $output->write('<info>Checking...</info>');

        return $linter->lint([], $cache);
    }

    /**
     * @throws InvalidStyleException
     */
    protected function showErrors(array $errors): void
    {
        $i = 0;
        $this->output->writeln("\nThere was " . count($errors) . ' errors:');

        foreach ($errors as $filename => $error) {
            $this->output->writeln('<comment>' . ++$i . ". {$filename}:{$error['line']}" . '</comment>');

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
        $lineLength = strlen(key($lines) + 1);
        $snippet = '';

        foreach ($lines as $i => $line) {
            $snippet .= (abs($lineNumber) === $i + 1 ? '  > ' : '    ');
            $snippet .= str_pad($i + 1, $lineLength, ' ', STR_PAD_LEFT) . '| ' . rtrim($line) . PHP_EOL;
        }

        return $snippet;
    }

    /**
     * @throws InvalidStyleException
     */
    public function getHighlightedCodeSnippet(string $filePath, int $lineNumber, int $linesBefore = 3, int $linesAfter = 3): string
    {
        if (
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

    protected function mergeOptions(): array
    {
        $options = $this->input->getOptions();
        $options['path'] = $this->input->getArgument('path');
        $options['cache'] = $this->input->getOption('cache');
        if ($options['warning'] === false) {
            unset($options['warning']);
        }

        $config = [];

        if (!$this->input->getOption('no-configuration')) {
            $filename = $this->getConfigFile();

            if (empty($options['configuration']) && $filename) {
                $options['configuration'] = $filename;
            }

            if (!empty($options['configuration'])) {
                $this->output->writeln("<comment>Loaded config from \"{$options['configuration']}\"</comment>\n");
                $config = $this->loadConfiguration($options['configuration']);
            } else {
                $this->output->writeln("<comment>No config file loaded.</comment>\n");
            }
        } else {
            $this->output->writeln("<comment>No config file loaded.</comment>\n");
        }

        $options = array_merge($this->defaults, array_filter($config), array_filter($options));

        is_array($options['extensions']) || $options['extensions'] = explode(',', $options['extensions']);

        return $options;
    }

    protected function getConfigFile(): false|string
    {
        $inputPath = $this->input->getArgument('path');

        $dir = './';

        if (1 == count($inputPath) && $first = reset($inputPath)) {
            $dir = is_dir($first) ? $first : dirname($first);
        }

        $filename = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.phplint.yml';

        return realpath($filename);
    }

    protected function loadConfiguration(string $path): array
    {
        try {
            $configuration = Yaml::parse(file_get_contents($path));
            if (!is_array($configuration)) {
                throw new ParseException('Invalid content.', 1);
            }

            return $configuration;
        } catch (ParseException $e) {
            $this->output->writeln(sprintf('<error>Unable to parse the YAML string: %s</error>', $e->getMessage()));

            return [];
        }
    }
}
