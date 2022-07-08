<?php

namespace Overtrue\PHPLint;

use InvalidArgumentException;
use Overtrue\PHPLint\Process\Lint;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Linter
{
    private ?\Closure $processCallback = null;
    private array $files = [];
    private array $cache = [];
    private array $paths;
    private array $excludes;
    private array $extensions;
    private int $processLimit = 5;
    private bool $warning;
    private string $memoryLimit;

    public function __construct(array|string $paths, array $excludes = [], array $extensions = ['php'], $warning = false)
    {
        $this->paths = (array)$paths;
        $this->excludes = $excludes;
        $this->warning = $warning;
        $this->extensions = \array_map(function ($extension) {
            return \sprintf('*.%s', \ltrim($extension, '.'));
        }, $extensions);
    }

    public function lint(array $files = [], bool $cache = true): array
    {
        if (empty($files)) {
            $files = $this->getFiles();
        }

        $processCallback = $this->processCallback ?? fn () => null;

        $errors = [];
        $running = [];
        $newCache = [];

        while (!empty($files) || !empty($running)) {
            for ($i = count($running); !empty($files) && $i < $this->processLimit; ++$i) {
                $file = array_shift($files);
                $filename = $file->getRealPath();
                $relativePathname = $file->getRelativePathname();
                if (!isset($this->cache[$relativePathname]) || $this->cache[$relativePathname] !== md5_file($filename)) {
                    $lint = $this->createLintProcess($filename);
                    $running[$filename] = [
                        'process' => $lint,
                        'file' => $file,
                        'relativePath' => $relativePathname,
                    ];
                    $lint->start();
                } else {
                    $newCache[$relativePathname] = $this->cache[$relativePathname];
                }
            }

            foreach ($running as $filename => $item) {
                /** @var Lint $lint */
                $lint = $item['process'];

                if ($lint->isRunning()) {
                    continue;
                }

                unset($running[$filename]);

                if ($lint->hasSyntaxError()) {
                    $processCallback('error', $item['file']);
                    $errors[$filename] = array_merge(['file' => $filename, 'file_name' => $item['relativePath']], $lint->getSyntaxError());
                } elseif ($this->warning && $lint->hasSyntaxIssue()) {
                    $processCallback('warning', $item['file']);
                    $errors[$filename] = array_merge(['file' => $filename, 'file_name' => $item['relativePath']], $lint->getSyntaxIssue());
                } else {
                    $newCache[$item['relativePath']] = md5_file($filename);
                    $processCallback('ok', $item['file']);
                }
            }
        }

        $cache && Cache::put($newCache);

        return $errors;
    }

    public function setCache(array $cache = [])
    {
        $this->cache = $cache;
    }

    public function setMemoryLimit(string $limit)
    {
        $this->memoryLimit = $limit;
    }

    public function getFiles(): array
    {
        if (empty($this->files)) {
            foreach ($this->paths as $path) {
                if (is_dir($path)) {
                    $this->files = array_merge($this->files, $this->getFilesFromDir($path));
                } elseif (is_file($path)) {
                    $this->files[$path] = new SplFileInfo($path, $path, $path);
                }
            }
        }

        return $this->files;
    }

    protected function getFilesFromDir(string $dir): array
    {
        $finder = new Finder();
        $finder->files()
            ->ignoreUnreadableDirs()
            ->ignoreVCS(true)
            ->filter(function (SplFileInfo $file) {
                return $file->isReadable();
            })
            ->in(realpath($dir));

        array_map([$finder, 'name'], $this->extensions);
        array_map([$finder, 'notPath'], $this->excludes);

        return iterator_to_array($finder);
    }

    public function setFiles(array $files): static
    {
        foreach ($files as $file) {
            if (is_file($file)) {
                $file = new SplFileInfo($file, $file, $file);
            }

            if (!($file instanceof SplFileInfo)) {
                throw new InvalidArgumentException("File $file not exists.");
            }

            $this->files[$file->getRealPath()] = $file;
        }

        return $this;
    }

    public function setProcessCallback(callable $processCallback): static
    {
        $this->processCallback = \Closure::fromCallable($processCallback);

        return $this;
    }

    public function setProcessLimit(int $processLimit): static
    {
        $this->processLimit = $processLimit;

        return $this;
    }

    protected function createLintProcess(string $filename): Lint
    {
        $command = [
            PHP_SAPI == 'cli' ? PHP_BINARY : PHP_BINDIR . '/php',
            '-d error_reporting=E_ALL',
            '-d display_errors=On',
        ];

        if (!empty($this->memoryLimit)) {
            $command[] = '-d memory_limit=' . $this->memoryLimit;
        }

        $command[] = '-l';
        $command[] = $filename;

        return new Lint($command);
    }
}
