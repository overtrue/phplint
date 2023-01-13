<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Tests\Configuration;

use Overtrue\PHPLint\Command\LintCommand;
use Overtrue\PHPLint\Configuration\ConfigResolver;
use Overtrue\PHPLint\Console\Application;
use Overtrue\PHPLint\Tests\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use function ini_get;

/**
 * @author Laurent Laville
 * @since Release 7.0.0
 */
final class ConfigResolverTest extends TestCase
{
    private static Command $command;

    public static function setupBeforeClass(): void
    {
        $application = new Application();
        $application->add(new LintCommand());

        self::$command = $application->find('phplint');
    }

    /**
     * @covers \Overtrue\PHPLint\Configuration\ConfigResolver::resolve
     */
    public function testResolveConfiguration(): void
    {
        $arguments = [
            'path' => [__DIR__],
            '--no-configuration' => true,
            '--no-cache' => true,
        ];
        $resolver = $this->getConfigResolver($arguments);

        $expected = [
            'quiet' => false,
            'jobs' => ConfigResolver::DEFAULT_JOBS,
            'path' => [__DIR__],
            'exclude' => [],
            'extensions' => ConfigResolver::DEFAULT_EXTENSIONS,
            'warning' => false,
            'cache' => ConfigResolver::DEFAULT_CACHE_DIR,
            'no-cache' => true,
            'configuration' => '',
            'memory-limit' => ini_get('memory_limit'),
            'json' => false,
            'xml' => false,
            'no-files-exit-code' => false,
        ];
        $config = $resolver->resolve();

        $this->assertSame($expected, $config);
    }

    private function getConfigResolver(array $arguments): ConfigResolver
    {
        $input = new ArrayInput($arguments);
        // bind the input against the command specific arguments/options
        $input->bind(self::$command->getDefinition());

        return new ConfigResolver($input);
    }
}