<?php

/*
 * This file is part of the overtrue/phplint
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\PHPLint\Command;

use DateTime;
use Exception;
use JakubOnderka\PhpConsoleColor\ConsoleColor;
use JakubOnderka\PhpConsoleHighlighter\Highlighter;
use N98\JUnitXml\Document;
use Overtrue\PHPLint\Cache;
use Overtrue\PHPLint\Linter;
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

/**
 * Class LintCommand.
 */
class LintCommand extends Command
{
    /**
     * @var array
     */
    protected $defaults = [
        'jobs' => 5,
        'path' => '.',
        'exclude' => [],
        'extensions' => ['php'],
        'warning' => false
    ];

    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $input;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    /**
     * Configures the current command.
     */
    protected function configure()
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
                'Allow to silenty fail.'
            );
    }

    /**
     * Initializes the command just after the input has been validated.
     *
     * This is mainly useful when a lot of commands extends one main command
     * where some things need to be initialized based on the input arguments and options.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    public function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @throws \LogicException When this abstract method is not implemented
     *
     * @return null|int null or 0 if everything went fine, or an error code
     *
     * @see setCode()
     *
     * @throws \JakubOnderka\PhpConsoleColor\InvalidStyleException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
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

        $fileCount = count($linter->getFiles());

        if ($fileCount <= 0) {
            $output->writeln('<info>Could not find files to lint</info>');

            return 0;
        }

        $errors = $this->executeLint($linter, $input, $output, $fileCount);

        $timeUsage = Helper::formatTime(microtime(true) - $startTime);
        $memUsage = Helper::formatMemory(memory_get_usage(true) - $startMemUsage);

        $code = 0;
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

    /**
     * @param string $path
     * @param array  $errors
     * @param array  $options
     * @param array  $context
     */
    protected function dumpJsonResult($path, array $errors, array $options, array $context = [])
    {
        $result = [
            'status' => 'success',
            'options' => $options,
            'errors' => $errors,
        ];

        \file_put_contents($path, \json_encode(\array_merge($result, $context)));
    }

    /**
     * @param string $path
     * @param array  $errors
     * @param array  $options
     * @param array  $context
     *
     * @throws Exception
     */
    protected function dumpXmlResult($path, array $errors, array $options, array $context = [])
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

    /**
     * Execute lint and return errors.
     *
     * @param Linter          $linter
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param int             $fileCount
     *
     * @return array
     */
    protected function executeLint($linter, $input, $output, $fileCount)
    {
        $cache = !$input->getOption('no-cache');
        $maxColumns = floor((new Terminal())->getWidth() / 2);
        $verbosity = $output->getVerbosity();
        $displayProgress = !$input->getOption('no-progress');

        $displayProgress && $linter->setProcessCallback(function ($status, SplFileInfo $file) use ($output, $verbosity, $fileCount, $maxColumns) {
            static $i = 1;

            $percent = floor(($i / $fileCount) * 100);
            $process = str_pad(" {$i} / {$fileCount} ({$percent}%)", 18, ' ', STR_PAD_LEFT);

            if ($verbosity >= OutputInterface::VERBOSITY_VERBOSE) {
                $filename = str_pad(" {$i}: " . $file->getRelativePathname(), $maxColumns - 10, ' ', \STR_PAD_RIGHT);
                if ($status === 'ok') {
                    $status = '<info>OK</info>';
                } elseif ($status === 'error') {
                    $status = '<error>Error</error>';
                } else {
                    $status = '<error>Warning</error>';
                }

                $status = \str_pad($status, 20, ' ', \STR_PAD_RIGHT);
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
     * Show errors detail.
     *
     * @param array $errors
     *
     * @throws \JakubOnderka\PhpConsoleColor\InvalidStyleException
     */
    protected function showErrors($errors)
    {
        $i = 0;
        $this->output->writeln("\nThere was " . count($errors) . ' errors:');

        foreach ($errors as $filename => $error) {
            $this->output->writeln('<comment>' . ++$i . ". {$filename}:{$error['line']}" . '</comment>');

            $this->output->write($this->getHighlightedCodeSnippet($filename, $error['line']));

            $this->output->writeln("<error> {$error['error']}</error>");
        }
    }

    /**
     * @param string $filePath
     * @param int    $lineNumber
     * @param int    $linesBefore
     * @param int    $linesAfter
     *
     * @return string
     */
    protected function getCodeSnippet($filePath, $lineNumber, $linesBefore = 3, $linesAfter = 3)
    {
        $lines = file($filePath);
        $offset = $lineNumber - $linesBefore - 1;
        $offset = max($offset, 0);
        $length = $linesAfter + $linesBefore + 1;
        $lines = array_slice($lines, $offset, $length, $preserveKeys = true);
        end($lines);
        $lineStrlen = strlen(key($lines) + 1);
        $snippet = '';

        foreach ($lines as $i => $line) {
            $snippet .= (abs($lineNumber) === $i + 1 ? '  > ' : '    ');
            $snippet .= str_pad($i + 1, $lineStrlen, ' ', STR_PAD_LEFT) . '| ' . rtrim($line) . PHP_EOL;
        }

        return $snippet;
    }

    /**
     * @param string $filePath
     * @param int    $lineNumber
     * @param int    $linesBefore
     * @param int    $linesAfter
     *
     * @return string
     *
     * @throws \JakubOnderka\PhpConsoleColor\InvalidStyleException
     */
    public function getHighlightedCodeSnippet($filePath, $lineNumber, $linesBefore = 3, $linesAfter = 3)
    {
        if (
            !class_exists('\JakubOnderka\PhpConsoleHighlighter\Highlighter') ||
            !class_exists('\JakubOnderka\PhpConsoleColor\ConsoleColor')
        ) {
            return $this->getCodeSnippet($filePath, $lineNumber, $linesBefore, $linesAfter);
        }

        $colors = new ConsoleColor();
        $highlighter = new Highlighter($colors);
        $fileContent = file_get_contents($filePath);

        return $highlighter->getCodeSnippet($fileContent, $lineNumber, $linesBefore, $linesAfter);
    }

    /**
     * Merge options.
     *
     * @return array
     */
    protected function mergeOptions()
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

    /**
     * Get configuration file.
     *
     * @return string|null
     */
    protected function getConfigFile()
    {
        $inputPath = $this->input->getArgument('path');

        $dir = './';

        if (1 == count($inputPath) && $first = reset($inputPath)) {
            $dir = is_dir($first) ? $first : dirname($first);
        }

        $filename = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.phplint.yml';

        return realpath($filename);
    }

    /**
     * Load configuration from yaml.
     *
     * @param string $path
     *
     * @return array
     */
    protected function loadConfiguration($path)
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
