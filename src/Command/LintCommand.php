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

use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Configuration\Resolver\ConfigValueResolver;
use Overtrue\PHPLint\Configuration\Resolver\DryRunValueResolver;
use Overtrue\PHPLint\Configuration\Resolver\FileExtensionValueResolver;
use Overtrue\PHPLint\Configuration\Resolver\IgnoreExitCodeValueResolver;
use Overtrue\PHPLint\Configuration\Resolver\JobValueResolver;
use Overtrue\PHPLint\Configuration\Resolver\LoggerValueResolver;
use Overtrue\PHPLint\Configuration\Resolver\MemoryLimitValueResolver;
use Overtrue\PHPLint\Configuration\Resolver\MetadataValueResolver;
use Overtrue\PHPLint\Configuration\Resolver\OutputFileResolver;
use Overtrue\PHPLint\Configuration\Resolver\OutputFormatResolver;
use Overtrue\PHPLint\Configuration\Resolver\PathValueResolver;
use Overtrue\PHPLint\Configuration\Resolver\ShowWarningsValueResolver;
use Overtrue\PHPLint\Console\Attribute\ValueResolver;
use Overtrue\PHPLint\Console\SectionEnum;
use Overtrue\PHPLint\Finder;
use Overtrue\PHPLint\Linter;
use Overtrue\PHPLint\Metadata\MetadataCollection;
use Overtrue\PHPLint\Output\LinterOutput;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder as SymfonyFinder;
use Throwable;

use function json_encode;

use const JSON_UNESCAPED_SLASHES;

/**
 * @author Overtrue
 * @author Laurent Laville (since v9.0)
 */
#[AsCommand(name: 'lint', description: 'Files syntax check only')]
final class LintCommand
{
    private LinterOutput $results;

    /**
     * @deprecated since release 9.8.0, and will be removed in API next version;
     *             replaced by "\Overtrue\PHPLint\Metadata\LinterOutput"
     */
    public function getResults(): LinterOutput
    {
        if (!isset($this->results)) {
            $this->results = new LinterOutput([], new SymfonyFinder());
        }
        return $this->results;
    }

    /**
     * @throws Throwable
     */
    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        Command $command,
        #[ValueResolver(LoggerValueResolver::class)]
        LoggerInterface $logger,
        #[ValueResolver(MetadataValueResolver::class)]
        MetadataCollection $metadataCollection,
        #[ValueResolver(ConfigValueResolver::class)]
        string $configuration = OptionDefinition::DEFAULT_CONFIG_FILE, // global option
        #[Argument(
            description: 'Path to file or directory to lint (<comment>default: working directory</comment>)',
            name: OptionDefinition::PATH
        )]
        #[ValueResolver(PathValueResolver::class)]
        ?array $sourcePath = null,
        #[Option(
            description: 'Path to file or directory to exclude from linting',
            name: OptionDefinition::EXCLUDE,
        )]
        #[ValueResolver(PathValueResolver::class)]
        ?array $excludePath = null,
        #[Option(
            description: 'Check only files with selected extensions',
            name: OptionDefinition::FILE_EXTENSIONS,
        )]
        #[ValueResolver(FileExtensionValueResolver::class)]
        ?array $fileExtensions = null,
        #[Option(
            description: 'Number of paralleled jobs to run',
            name: OptionDefinition::JOBS,
            shortcut: 'j',
        )]
        #[ValueResolver(JobValueResolver::class)]
        ?int $parallelJob = null,
        #[Option(
            description: 'Also show warnings',
            name: OptionDefinition::WARNING,
            shortcut: 'w',
        )]
        #[ValueResolver(ShowWarningsValueResolver::class)]
        bool $showWarning = false,
        #[Option(
            description: 'Memory limit for analysis',
            name: OptionDefinition::OPTION_MEMORY_LIMIT,
        )]
        #[ValueResolver(MemoryLimitValueResolver::class)]
        ?int $memoryLimit = null,
        #[Option(
            description: 'Ignore exit codes so there are no "failure" exit code even when no files processed',
            name: OptionDefinition::IGNORE_EXIT_CODE,
        )]
        #[ValueResolver(IgnoreExitCodeValueResolver::class)]
        bool $ignoreExitCode = false,
        #[Option(
            description: 'Only shows which files would have been analysed',
            name: OptionDefinition::DRY_RUN,
        )]
        #[ValueResolver(DryRunValueResolver::class)]
        bool $dryRun = false,
        #[Option(name: OptionDefinition::OUTPUT_FORMAT)]  // option dynamically added by the "output_manager" extension
        #[ValueResolver(OutputFormatResolver::class)]
        ?array $outputFormat = null,
        #[Option(name: OptionDefinition::OUTPUT_FILE)]  // option dynamically added by the "output_manager" extension
        #[ValueResolver(OutputFileResolver::class)]
        ?string $outputFile = null,
    ): int {
        $message = sprintf(
            '<comment>%s</comment> %s',
            'The "{command}" command was invoked with following parameters',
            ': {parameters}'
        );

        $invokableCommand = $command->getCode();
        $parameters = $invokableCommand?->getArguments($input) ?? [];

        $valueResolvedDump = is_scalar($parameters)
            ? $parameters
            : (is_object($parameters) ? get_debug_type($parameters): json_encode($parameters, JSON_UNESCAPED_SLASHES));

        $logger->notice(
            $message,
            [
                '__section__' => SectionEnum::COMMAND->label(),
                '__style__' => SectionEnum::COMMAND->value,
                'command' => $command->getName(),
                'parameters' => $valueResolvedDump
            ]
        );

        $finder = new Finder(null, $sourcePath, $excludePath, $fileExtensions);

        $message = sprintf(
            '<comment>%s</comment> %s',
            'The "Finder" rules was applied',
            ': {rules}'
        );
        $logger->debug(
            $message,
            [
                '__section__' => SectionEnum::COMMAND->label(),
                '__style__' => SectionEnum::COMMAND->value,
                'rules' => json_encode($finder, JSON_UNESCAPED_SLASHES)
            ]
        );
        $finder = $finder->getFiles();

        $application = $command->getApplication();

        $linter = new Linter(
            null,
            $application->getDispatcher(),   // @phpstan-ignore method.notFound
            $application,
            $application->getHelperSet(),
            $output,
            $application->getCache(),
            $parallelJob,
            $dryRun,
            $showWarning,
            $memoryLimit,
        );
        $linter->setLogger($logger);
        $this->results = $linter->lintFiles($finder, null, $metadataCollection);

        if ($ignoreExitCode) {
            return Command::SUCCESS;
        }

        if ($linter->count() === 0 || $linter->hasFailures()) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
