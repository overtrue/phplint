<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Tests\EndToEnd;

use Overtrue\PHPLint\Command\LintCommand;
use Overtrue\PHPLint\Console\Application;
use Overtrue\PHPLint\Tests\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @author Laurent Laville
 * @since Release 7.0.0
 */
final class LintCommandTest extends TestCase
{
    private ?CommandTester $commandTester;

    protected function setUp(): void
    {
        $application = new Application();
        $application->add(new LintCommand());

        $command = $application->find('phplint');

        $this->commandTester = new CommandTester($command);
    }

    protected function tearDown(): void
    {
        $this->commandTester = null;
    }

    /**
     * @covers \Overtrue\PHPLint\Command\LintCommand
     */
    public function testLintDirectoryWithoutConfigurationAndCache(): void
    {
        $arguments = [
            'path' => [__DIR__],
            '--no-configuration' => true,
            '--no-cache' => true,
        ];
        $this->commandTester->execute($arguments);

        $this->commandTester->assertCommandIsSuccessful();
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Cache: 0 hit, 1 miss', $display);
    }

    /**
     * @covers \Overtrue\PHPLint\Command\LintCommand
     */
    public function testLintSyntaxErrorFileWithoutConfigurationAndCache(): void
    {
        $arguments = [
            'path' => [\dirname(__DIR__) . '/fixtures/syntax_error.php'],
            '--no-configuration' => true,
            '--no-cache' => true,
        ];
        $this->commandTester->execute($arguments);

        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('1 file, 1 error', $display);
    }
}
