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

namespace Overtrue\PHPLint\Environment\Provider;

use Overtrue\PHPLint\Environment\ProviderData;
use Overtrue\PHPLint\Environment\ProviderInterface;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

use function escapeshellarg;
use function file_exists;
use function getcwd;
use function preg_match;
use function sprintf;
use function trim;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
class Git implements ProviderInterface
{
    public function __construct(
        private ?string $workingDirectory = null,
        private ?ExecutableFinder $exeFinder = null
    ) {
        $this->exeFinder ??= new ExecutableFinder();
        $this->workingDirectory ??= getcwd();
    }

    public function describe(): ?array
    {
        $binaryPath = $this->exeFinder->find('git', null);
        $dotGit = sprintf('%s/.git', $this->workingDirectory);
        if (false === ($binaryPath && file_exists($dotGit))) {
            return null;
        }

        $cmd = sprintf('%s %s', escapeshellarg($binaryPath), '--version');
        $process = Process::fromShellCommandline($cmd, $this->workingDirectory);
        $exitCode = $process->run();

        if ($exitCode === 0) {
            preg_match('/^git version\s(.*)/', $process->getOutput(), $matches);
            $version = $matches[1];
        }

        $cmd = sprintf('%s %s', escapeshellarg($binaryPath), 'rev-parse --abbrev-ref HEAD');
        $process = Process::fromShellCommandline($cmd, $this->workingDirectory);
        $exitCode = $process->run();

        if ($exitCode === 0) {
            $branchName = trim($process->getOutput());
        }

        $cmd = sprintf('%s %s', escapeshellarg($binaryPath), 'log -1 --format="%H"');
        $process = Process::fromShellCommandline($cmd, $this->workingDirectory);
        $exitCode = $process->run();

        if ($exitCode === 0) {
            $commitHash = trim($process->getOutput());
        }

        return [
            new ProviderData('binary_path', $binaryPath, 'The PHP interpreter binary path'),
            new ProviderData('version', $version ?? 'UNKNOWN', 'The Version Control interpreter'),
            new ProviderData('system', 'git', 'The Version Control System used'),
            new ProviderData('branch', $branchName ?? 'UNKNOWN', 'The branch name'),
            new ProviderData('commit', $commitHash ?? 'UNKNOWN', 'The latest commit hash'),
        ];
    }
}
