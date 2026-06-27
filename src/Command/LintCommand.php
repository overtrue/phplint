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

namespace Overtrue\PHPLint\Command;

use Overtrue\PHPLint\Configuration\ConsoleOptionsResolver;
use Overtrue\PHPLint\Configuration\FileOptionsResolver;
use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Finder;
use Overtrue\PHPLint\Linter;
use Overtrue\PHPLint\Output\LinterOutput;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder as SymfonyFinder;
use Throwable;

use function count;
use function microtime;

/**
 * @author Overtrue
 * @author Laurent Laville (since v9.0)
 */
#[AsCommand(name: 'lint', description: 'Files syntax check only')]
final class LintCommand
{
    private LinterOutput $results;

    public function __construct()
    {
        $this->results = new LinterOutput([], new SymfonyFinder());
    }

    public function getResults(): LinterOutput
    {
        return $this->results;
    }

    private function initialize(InputInterface $input, OutputInterface $output): void
    {
        $cmdName = 'lint';

        // initializes correctly command and path arguments when lint is set as default command
        $cmd = $input->getArgument('command');
        $paths = $input->getArgument('path');
        if ($cmd !== $cmdName) {
            array_unshift($paths, $cmd);
        }
        $input->setArgument('path', $paths);
        $input->setArgument('command', $cmdName);
    }

    /**
     * @throws Throwable
     */
    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        Application $application,
        #[Argument(
            description: 'Path to file or directory to lint (<comment>default: working directory</comment>)',
            name: OptionDefinition::PATH
        )]
        ?array $sourcePath = null,
        #[Option(
            description: 'Path to file or directory to exclude from linting',
            name: OptionDefinition::EXCLUDE,
        )]
        ?array $excludePath = null,
        #[Option(
            description: 'Check only files with selected extensions',
            name: OptionDefinition::EXTENSIONS,
        )]
        ?array $extensions = null,
        #[Option(
            description: 'Number of paralleled jobs to run',
            name: OptionDefinition::JOBS,
            shortcut: 'j',
        )]
        ?int $job = null,
        #[Option(
            description: 'Read configuration from config file',
            name: OptionDefinition::CONFIGURATION,
            shortcut: 'c',
        )]
        string $configuration = OptionDefinition::DEFAULT_CONFIG_FILE,
        #[Option(
            description: 'Ignore default configuration file (<comment>' . OptionDefinition::DEFAULT_CONFIG_FILE . '</comment>)',
            name: OptionDefinition::NO_CONFIGURATION,
        )]
        bool $noConfig = false,
        #[Option(
            description: 'Path to the cache directory (<comment>Deprecated option, use "cache-dir" instead</comment>)',
            name: OptionDefinition::CACHE,
        )]
        ?string $cachePath = null,
        #[Option(
            description: 'Path to the cache directory',
            name: OptionDefinition::CACHE_DIR,
        )]
        ?string $cacheDir = null,
        #[Option(
            description: 'Limit cached data for a period of time'
            . ' (<info>>0: time to live in seconds</info>)',
            name: 'cache-ttl',
        )]
        int $cacheTtl = OptionDefinition::DEFAULT_CACHE_TTL,
        #[Option(
            description: 'Ignore cached data',
            name: OptionDefinition::NO_CACHE,
        )]
        ?bool $noCache = null,
        #[Option(
            description: 'Also show warnings',
            name: OptionDefinition::WARNING,
            shortcut: 'w',
        )]
        ?bool $showWarning = null,
        #[Option(
            description: 'Memory limit for analysis',
            name: OptionDefinition::OPTION_MEMORY_LIMIT,
        )]
        ?int $memoryLimit = null,
        #[Option(
            description: 'Ignore exit codes so there are no "failure" exit code even when no files processed',
            name: OptionDefinition::IGNORE_EXIT_CODE,
        )]
        ?bool $ignoreExitCode = null,
        #[Option(
            description: 'A PHP script that is included before the linter run',
            name: OptionDefinition::BOOTSTRAP,
        )]
        ?string $bootstrap = null,
    ): int {
        $startTime = microtime(true);

        $this->initialize($input, $output);

        if (true === $noConfig) {
            $configResolver = new ConsoleOptionsResolver($input);
        } else {
            $configResolver = new FileOptionsResolver($input);
        }

        $finder = (new Finder($configResolver))->getFiles();
        $linter = new Linter(
            $configResolver,
            $application->getEventDispatcher(),   // @phpstan-ignore method.notFound
            $application,
            $application->getHelperSet(),
            $output
        );
        $this->results = $linter->lintFiles($finder, $startTime);

        $data = $this->results->getFailures();

        if ($configResolver->getOption(OptionDefinition::IGNORE_EXIT_CODE)) {
            return Command::SUCCESS;
        }

        if (count($this->results) === 0 || count($data)) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
