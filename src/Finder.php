<?php

declare(strict_types=1);

namespace Overtrue\PHPLint;

use ArrayIterator;
use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Configuration\Resolver;
use Symfony\Component\Finder\Finder as SymfonyFinder;
use Symfony\Component\Finder\SplFileInfo;

use function implode;
use function is_dir;
use function is_file;
use function realpath;
use function sprintf;

/**
 * Finder allows to find files and directories.
 *
 * @author Laurent Laville
 * @since Release 9.0.0
 */
final class Finder
{
    private array $paths;
    private array $excludes;
    private array $extensions;

    public function __construct(Resolver $configResolver)
    {
        $this->paths = $configResolver->getOption(OptionDefinition::OPTION_PATH);
        $this->excludes = $configResolver->getOption(OptionDefinition::OPTION_EXCLUDE);
        $this->extensions = $configResolver->getOption(OptionDefinition::OPTION_EXTENSIONS);
    }

    public function getFiles(): SymfonyFinder
    {
        $finder = new SymfonyFinder();
        foreach ($this->paths as $path) {
            if (is_dir($path)) {
                $finder->append($this->getFilesFromDir($path));
            } elseif (is_file($path)) {
                $iterator = new ArrayIterator();
                $iterator[$path] = new SplFileInfo($path, $path, $path);
                $finder->append($iterator);
            }
        }
        return $finder;
    }
    private function getFilesFromDir(string $dir): SymfonyFinder
    {
        $finder = new SymfonyFinder();
        $finder->files()
            ->ignoreUnreadableDirs()
            ->filter(function (SplFileInfo $file) {
                return $file->isReadable();
            })
            ->name(sprintf('/\\.(%s)$/', implode('|', $this->extensions)))
            ->notPath($this->excludes)
            ->in(realpath($dir));

        return $finder;
    }
}
