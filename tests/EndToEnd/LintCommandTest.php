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

namespace Overtrue\PHPLint\Tests\EndToEnd;

use Overtrue\PHPLint\Command\LintCommand;
use Overtrue\PHPLint\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Tester\CommandTester;

use function dirname;

/**
 * @author Laurent Laville
 * @since Release 9.0.0
 */
#[CoversClass(LintCommand::class)]
final class LintCommandTest extends TestCase
{
    private ?CommandTester $commandTester;
    private LintCommand $invokableCommand;

    protected function setUp(): void
    {
        parent::setUp();

        $command = $this->application->find('lint');

        $this->invokableCommand = $command->getCode();

        $this->commandTester = new CommandTester($command);
    }

    protected function tearDown(): void
    {
        $this->commandTester = null;
    }

    public function testLintDirectoryWithoutConfigurationAndCache(): void
    {
        $arguments = [
            'path' => [__DIR__],
            '--no-configuration' => true,
            '--no-cache' => true,
        ];

        $this->commandTester->execute($arguments);

        $this->commandTester->assertCommandIsSuccessful();
        $this->assertCount(
            2,
            $this->invokableCommand->getResults()->getMisses()
        );
    }

    public function testLintSyntaxErrorFileWithoutConfigurationAndCache(): void
    {
        $arguments = [
            'path' => [dirname(__DIR__) . '/fixtures/syntax_error.php'],
            '--no-configuration' => true,
            '--no-cache' => true,
        ];

        $this->commandTester->execute($arguments);

        $this->assertCount(
            1,
            $this->invokableCommand->getResults()->getErrors()
        );
    }
}
