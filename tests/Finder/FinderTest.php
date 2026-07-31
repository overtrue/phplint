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

namespace Overtrue\PHPLint\Tests\Finder;

use Iterator;
use LogicException;
use Overtrue\PHPLint\Command\InvokableCommand;
use Overtrue\PHPLint\Configuration\FileOptionsResolver;
use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Finder;
use Overtrue\PHPLint\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

use function array_keys;
use function array_map;
use function dirname;
use function iterator_to_array;
use function str_replace;

/**
 * @author Laurent Laville
 * @since Release 9.0.0
 */
#[CoversClass(Finder::class)]
final class FinderTest extends TestCase
{
    public function testAllPhpFilesFoundShouldExists(): void
    {
        $basePath = dirname(__DIR__);

        $arguments =                 [
            OptionDefinition::PATH => [$basePath],
            '--' . OptionDefinition::CONFIGURATION => 'never',
            '--' . OptionDefinition::EXCLUDE => [],
            '--' . OptionDefinition::FILE_EXTENSIONS => ['php'],
        ];

        $finder = $this->getFinder($arguments);

        foreach ($finder->getFiles() as $file) {
            $this->assertFileExists($file->getRealPath());
        }
    }

    public function testAllPathShouldExistsAndReadable(): void
    {
        $this->expectException(LogicException::class);

        $basePath = dirname(__DIR__);

        $arguments = [
            OptionDefinition::PATH => [$basePath . '/fixtures/missing_dir'],
            '--' . OptionDefinition::CONFIGURATION => 'never',
        ];

        $finder = $this->getFinder($arguments);

        $this->assertGreaterThan(0, count($finder->getFiles()));
    }

    public function testSearchPhpFilesWithCondition(): void
    {
        $basePath = dirname(__DIR__);

        $arguments = [
            OptionDefinition::PATH => [$basePath],
            '--' . OptionDefinition::CONFIGURATION => 'never',
            '--' . OptionDefinition::EXCLUDE => ['fixtures', 'Benchmark'],
            '--' . OptionDefinition::FILE_EXTENSIONS => ['php']
        ];

        $finder = $this->getFinder($arguments);

        $this->assertEqualsCanonicalizing(
            [
                'Cache/CacheTest.php',
                'Configuration/ConsoleConfigTest.php',
                'Configuration/YamlConfigTest.php',
                'EndToEnd/LintCommandTest.php',
                'EndToEnd/Reserved@Keywords.php',
                'Finder/FinderTest.php',
                'Output/OutputTest.php',
                'TestCase.php',
            ],
            $this->getRelativePathFiles($finder->getFiles()->getIterator(), $basePath)
        );
    }

    private function getRelativePathFiles(Iterator $iterator, string $basePath): array
    {
        return array_map(
            static fn (string $filename) => str_replace($basePath . '/', '', $filename),
            array_keys(iterator_to_array($iterator))
        );
    }

    private function getFinder(array $arguments): Finder
    {
        $application = $this->getApplication();

        $command = $application->find('lint');
        $command->mergeApplicationDefinition();

        $input = new ArrayInput($arguments);
        $input->bind($command->getDefinition());

        $output = new BufferedOutput();

        /** @var InvokableCommand $invokableCommand */
        $invokableCommand = $command->getCode();

        $parameters = $invokableCommand->getArguments($input, $output);

        $resolver = new FileOptionsResolver($input, $parameters);

        return new Finder($resolver);
    }
}