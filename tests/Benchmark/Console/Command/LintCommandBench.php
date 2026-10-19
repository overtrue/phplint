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

namespace Overtrue\PHPLint\Tests\Benchmark\Console\Command;

use Overtrue\PHPLint\Environment\EnvConfig;
use Overtrue\PHPLint\Output\ConsoleOutput;
use Overtrue\PHPLint\Runtime\ConsoleApplicationRunner;
use PhpBench\Attributes as Bench;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Tester\CommandTester;
use Throwable;

use function array_merge;
use function dirname;
use function putenv;

/**
 * @author Laurent Laville
 * @since Release 9.1.0
 */
#[Bench\OutputTimeUnit('milliseconds')]
#[Bench\Iterations(10)]
final class LintCommandBench
{
    /**
     * @throws Throwable
     */
    public function benchJobs1(): void
    {
        $this->runCommand(['--jobs' => 1]);
    }

    /**
     * @throws Throwable
     */
    public function benchJobs5(): void
    {
        $this->runCommand(['--jobs' => 5]);
    }

    /**
     * @throws Throwable
     */
    public function benchJobs10(): void
    {
        $this->runCommand(['--jobs' => 10]);
    }

    /**
     * @throws Throwable
     */
    private function runCommand(array $arguments): void
    {
        putenv("PLINT_LOGGER='Psr\Log\NullLogger'");
        putenv("PLINT_DIAGNOSTIC=never");
        putenv("PLINT_ALLOW_PLUGINS=output_manager");
        putenv("PLINT_DEFAULT_PLUGINS=output_manager");

        $arguments = array_merge([
            'command' => 'lint',
            'path' => [dirname(__DIR__, 4) . '/vendor-bin/sarif/vendor/bartlett/sarif-php-converters/src'],
            '--configuration' => 'never',
            '--output-format' => 'json',
            '--verbose' => true,
            '--ignore-exit-code' => true,
        ], $arguments);

        $logger = new NullLogger();
        $envConfig = new EnvConfig();
        $input = new ArrayInput($arguments);
        $output = new ConsoleOutput();

        $runner = new ConsoleApplicationRunner($logger, $envConfig, $input, $output);
        $application = $runner->getApplication();
        $command = $application->find($arguments['command']);

        $commandTester = new CommandTester($command);
        $commandTester->execute($arguments);
    }
}
