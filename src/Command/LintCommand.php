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

use Overtrue\PHPLint\Configuration\LintCommandInput;
use Overtrue\PHPLint\Configuration\Resolver\LoggerValueResolver;
use Overtrue\PHPLint\Console\ApplicationInterface;
use Overtrue\PHPLint\Console\Attribute\MapInput;
use Overtrue\PHPLint\Console\Attribute\ValueResolver;
use Overtrue\PHPLint\Console\SectionEnum;
use Overtrue\PHPLint\Finder;
use Overtrue\PHPLint\Linter;
use Overtrue\PHPLint\Output\LinterOutput;

use Psr\Log\LoggerInterface;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
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
        OutputInterface $output,
        Command $command,
        #[ValueResolver(LoggerValueResolver::class)]
        LoggerInterface $logger,
        #[MapInput]
        LintCommandInput $userInput,
    ): int {
        $message = sprintf(
            '<comment>%s</comment> %s',
            'The "{command}" command was invoked with following parameters',
            ': {parameters}'
        );

        $logger->notice(
            $message,
            [
                '__section__' => SectionEnum::COMMAND->label(),
                '__style__' => SectionEnum::COMMAND->value,
                'command' => $command->getName(),
                'parameters' => json_encode($userInput, JSON_UNESCAPED_SLASHES)
            ]
        );

        $finder = new Finder(
            paths: $userInput->sourcePath,
            excludes: $userInput->excludePath,
            fileExtensions: $userInput->fileExtensions
        );

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

        if (!$application instanceof ApplicationInterface) {
            return Command::INVALID;
        }

        $linter = new Linter(
            null,
            $application->getDispatcher(),
            $application,
            $application->getHelperSet(),
            $output,
            $application->getCache(),
            $userInput->parallelJob,
            $userInput->dryRun,
            $userInput->showWarning,
            $userInput->memoryLimit,
        );
        $linter->setLogger($logger);
        $this->results = $linter->lintFiles($finder, null, $application->getMetadata());

        if ($userInput->ignoreExitCode) {
            return Command::SUCCESS;
        }

        if ($linter->count() === 0 || $linter->hasFailures()) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
