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

namespace Overtrue\PHPLint\Configuration;

use Overtrue\PHPLint\Configuration\Resolver\DryRunValueResolver;
use Overtrue\PHPLint\Configuration\Resolver\FileExtensionValueResolver;
use Overtrue\PHPLint\Configuration\Resolver\IgnoreExitCodeValueResolver;
use Overtrue\PHPLint\Configuration\Resolver\JobValueResolver;
use Overtrue\PHPLint\Configuration\Resolver\MemoryLimitValueResolver;
use Overtrue\PHPLint\Configuration\Resolver\PathValueResolver;
use Overtrue\PHPLint\Configuration\Resolver\ShowWarningsValueResolver;
use Overtrue\PHPLint\Console\Attribute\ValueResolver;

use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\Option;

/**
 * DTO for the 'lint' command.
 *
 * @author Laurent Laville
 * @since Release 9.8.1
 */
final class LintCommandInput
{
    #[Argument(
        description: 'Path to file or directory to lint (<comment>default: working directory</comment>)',
        name: OptionDefinition::PATH
    )]
    #[ValueResolver(PathValueResolver::class)]
    public ?array $sourcePath = null;

    #[Option(
        description: 'Path to file or directory to exclude from linting',
        name: OptionDefinition::EXCLUDE,
    )]
    #[ValueResolver(PathValueResolver::class)]
    public ?array $excludePath = null;

    #[Option(
        description: 'Check only files with selected extensions',
        name: OptionDefinition::FILE_EXTENSIONS,
    )]
    #[ValueResolver(FileExtensionValueResolver::class)]
    public ?array $fileExtensions = null;

    #[Option(
        description: 'Number of paralleled jobs to run',
        name: OptionDefinition::JOBS,
        shortcut: 'j',
    )]
    #[ValueResolver(JobValueResolver::class)]
    public ?int $parallelJob = null;

    #[Option(
        description: 'Also show warnings',
        name: OptionDefinition::WARNING,
        shortcut: 'w',
    )]
    #[ValueResolver(ShowWarningsValueResolver::class)]
    public bool $showWarning = false;

    #[Option(
        description: 'Memory limit for analysis',
        name: OptionDefinition::OPTION_MEMORY_LIMIT,
    )]
    #[ValueResolver(MemoryLimitValueResolver::class)]
    public ?int $memoryLimit = null;

    #[Option(
        description: 'Ignore exit codes so there are no "failure" exit code even when no files processed',
        name: OptionDefinition::IGNORE_EXIT_CODE,
    )]
    #[ValueResolver(IgnoreExitCodeValueResolver::class)]
    public bool $ignoreExitCode = false;

    #[Option(
        description: 'Only shows which files would have been analysed',
        name: OptionDefinition::DRY_RUN,
    )]
    #[ValueResolver(DryRunValueResolver::class)]
    public bool $dryRun = false;
}
