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
use Overtrue\PHPLint\Finder;
use Overtrue\PHPLint\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

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

        $finder = new Finder(null, [$basePath], [], ['php']);

        foreach ($finder->getFiles() as $file) {
            $this->assertFileExists($file->getRealPath());
        }
    }

    public function testAllPathShouldExistsAndReadable(): void
    {
        $this->expectException(LogicException::class);

        $basePath = dirname(__DIR__);

        $finder = new Finder(null, [$basePath . '/fixtures/missing_dir'], [], ['php']);

        $this->assertGreaterThan(0, count($finder->getFiles()));
    }

    public function testSearchPhpFilesWithCondition(): void
    {
        $basePath = dirname(__DIR__);

        $finder = new Finder(null, [$basePath], ['fixtures', 'Benchmark'], ['php']);

        $this->assertEqualsCanonicalizing(
            [
                'Cache/CacheTest.php',
                'Configuration/Resolver/CoreValueResolverTest.php',
                'Configuration/Resolver/MetadataValueResolverTest.php',
                'Configuration/Resolver/PathValueResolverTest.php',
                'Configuration/Resolver/PluginValueResolverTest.php',
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
}
