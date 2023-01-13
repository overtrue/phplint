<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Tests\Finder;

use Iterator;
use Overtrue\PHPLint\Configuration\ConfigResolver;
use Overtrue\PHPLint\Finder;
use Overtrue\PHPLint\Tests\TestCase;
use function array_keys;
use function array_map;
use function dirname;
use function iterator_to_array;
use function str_replace;

/**
 * @author Laurent Laville
 * @since Release 7.0.0
 */
final class FinderTest extends TestCase
{
    /**
     * @covers \Overtrue\PHPLint\Finder::getFiles
     */
    public function testAllPhpFilesFoundShouldExists(): void
    {
        $basePath = dirname(__DIR__);

        $finder = new Finder([
            ConfigResolver::OPTION_PATH => [$basePath],
            ConfigResolver::OPTION_EXCLUDE => [],
            ConfigResolver::OPTION_EXTENSIONS => ['php']
        ]);

        foreach ($finder->getFiles() as $file) {
            $this->assertFileExists($file->getRealPath());
        }
    }

    /**
     * @covers \Overtrue\PHPLint\Finder::getFiles
     */
    public function testSearchPhpFilesWithCondition(): void
    {
        $basePath = dirname(__DIR__);
        $finder = new Finder([
            ConfigResolver::OPTION_PATH => [$basePath],
            ConfigResolver::OPTION_EXCLUDE => ['fixtures'],
            ConfigResolver::OPTION_EXTENSIONS => ['php']
        ]);

        $this->assertEqualsCanonicalizing(
            [
                'Cache/CacheTest.php',
                'Configuration/ConfigResolverTest.php',
                'EndToEnd/LintCommandTest.php',
                'Finder/FinderTest.php',
                'TestCase.php',
            ],
            $this->getRelativePathFiles($finder->getFiles()->getIterator(), $basePath)
        );
    }

    private function getRelativePathFiles(Iterator $iterator, string $basePath): array
    {
        return array_map(function (string $filename) use ($basePath) {
                return str_replace($basePath . '/', '', $filename);
            },
            array_keys(iterator_to_array($iterator))
        );
    }
}
