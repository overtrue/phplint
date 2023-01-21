<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Command;

use Overtrue\PHPLint\Configuration\ConfigResolver;
use Overtrue\PHPLint\Extension\Reporter\ConsoleReporter;
use Overtrue\PHPLint\Extension\Reporter\JsonReporter;
use Overtrue\PHPLint\Extension\Reporter\JunitXmlReporter;
use Overtrue\PHPLint\Finder;
use Overtrue\PHPLint\Linter;
use Phar;
use PHP_Parallel_Lint\PhpConsoleColor\InvalidStyleException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Throwable;

use function count;
use function microtime;
use function sprintf;

final class LintCommand extends Command
{
    private InputInterface $input;
    private OutputInterface $output;

    protected function configure(): void
    {
        $this
            ->setName('phplint')
            ->setDescription('Lint something')
            ->addArgument(
                'path',
                InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                'Path to file or directory to lint',
                [ConfigResolver::DEFAULT_PATH]
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
                'Check only files with selected extensions',
                ConfigResolver::DEFAULT_EXTENSIONS
            )
            ->addOption(
                'jobs',
                'j',
                InputOption::VALUE_REQUIRED,
                'Number of paralleled jobs to run',
                ConfigResolver::DEFAULT_JOBS
            )
            ->addOption(
                'configuration',
                'c',
                InputOption::VALUE_REQUIRED,
                'Read configuration from config file',
                ConfigResolver::DEFAULT_CONFIG_FILE
            )
            ->addOption(
                'no-configuration',
                null,
                InputOption::VALUE_NONE,
                'Ignore default configuration file (<comment>' . ConfigResolver::DEFAULT_CONFIG_FILE . '</comment>)'
            )
            ->addOption(
                'no-cache',
                null,
                InputOption::VALUE_NONE,
                'Ignore cached data'
            )
            ->addOption(
                'cache',
                null,
                InputOption::VALUE_OPTIONAL,
                'Path to the cache directory',
                ConfigResolver::DEFAULT_CACHE_DIR
            )
            ->addOption(
                'no-progress',
                null,
                InputOption::VALUE_NONE,
                'Hide the progress output'
            )
            ->addOption(
                'progress',
                'p',
                InputOption::VALUE_REQUIRED,
                'Show the progress output',
                'printer'
            )
            ->addOption(
                'log-json',
                null,
                InputOption::VALUE_OPTIONAL,
                'Log scan results in JSON format to file',
                ConfigResolver::DEFAULT_STANDARD_OUTPUT
            )
            ->addOption(
                'log-xml',
                null,
                InputOption::VALUE_OPTIONAL,
                'Log scan results in JUnit XML format to file',
                ConfigResolver::DEFAULT_STANDARD_OUTPUT
            )
            ->addOption(
                'warning',
                'w',
                InputOption::VALUE_NONE,
                'Also show warnings'
            )
            ->addOption(
                'quiet',
                'q',
                InputOption::VALUE_NONE,
                'Allow to silently fail'
            )
            ->addOption(
                'no-files-exit-code',
                null,
                InputOption::VALUE_NONE,
                'Throw error if no files processed'
            );

        if (Phar::running()) {
            $this->addOption(
                'manifest',
                null,
                InputOption::VALUE_NONE,
                'Show which versions of dependencies are bundled'
            );
        }
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * @throws InvalidStyleException|Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->hasParameterOption('--manifest')) {
            $phar = new Phar($_SERVER['argv'][0]);
            $manifest = $phar->getMetadata();
            $output->writeln($manifest);
            return self::SUCCESS;
        }

        $startTime = microtime(true);
        $options = $this->mergeOptions();
        $finder = (new Finder($options))->getFiles();
        $fileCount = count($finder);
        $cacheHits = $cacheMisses = 0;
        $linter = new Linter($this->getApplication(), $options);
        $errors = $linter->lintFiles($finder, $cacheHits, $cacheMisses);

        $timeUsage = Helper::formatTime(microtime(true) - $startTime);
        $memUsage = Helper::formatMemory(memory_get_usage(true));
        $cacheUsage = sprintf(
            '%d hit%s, %d miss%s',
            $cacheHits,
            $cacheHits > 1 ? 's' : '',
            $cacheMisses,
            $cacheMisses > 1 ? 'es' : ''
        );

        $context = [
            'time_usage' => $timeUsage,
            'memory_usage' => $memUsage,
            'cache_usage' => $cacheUsage,
            'files_count' => $fileCount,
            'options_used' => $options,
        ];

        $reporter = new ConsoleReporter($input, $output, $context);
        $reporter->format($errors, '');

        if ($options[ConfigResolver::OPTION_JSON_FILE]) {
            $reporter = new JsonReporter($input, $output, $context);
            $reporter->format($errors, $options[ConfigResolver::OPTION_JSON_FILE]);
        }

        if ($options[ConfigResolver::OPTION_XML_FILE]) {
            $reporter = new JunitXmlReporter($input, $output, $context);
            $reporter->format($errors, $options[ConfigResolver::OPTION_XML_FILE]);
        }

        if ($fileCount === 0) {
            if (!empty($options[ConfigResolver::OPTION_NO_FILES_EXIT_CODE])) {
                return self::FAILURE;
            }
        }

        if (count($errors) && empty($options[ConfigResolver::OPTION_QUIET])) {
            return self::FAILURE;
        }
        return self::SUCCESS;
    }

    private function mergeOptions(): array
    {
        $configResolver = new ConfigResolver($this->input);
        $options = $configResolver->resolve();
        $failures = $configResolver->getNestedExceptions();

        if (!empty($failures) && !$failures[0] instanceof ParseException) {
            throw $failures[0];
        }

        return $options;
    }
}
