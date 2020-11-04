<?php

/*
 * This file is part of the overtrue/phplint
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\PHPLint;

use InvalidArgumentException;
use Overtrue\PHPLint\Process\Lint;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class Linter.
 */
class Linter
{
    /**
     * @var callable
     */
    private $processCallback;

    /**
     * @var SplFileInfo[]
     */
    private $files = [];

    /**
     * @var array
     */
    private $cache = [];

    /**
     * @var array
     */
    private $path;

    /**
     * @var array
     */
    private $excludes;

    /**
     * @var array
     */
    private $extensions;

    /**
     * @var int
     */
    private $processLimit = 5;

    /**
     * @var bool
     */
    private $warning;

    /**
     * Constructor.
     *
     * @param string|array $path
     * @param array        $excludes
     * @param array        $extensions
     * @param bool         $warning
     */
    public function __construct($path, array $excludes = [], array $extensions = ['php'], $warning = false)
    {
        $this->path = (array)$path;
        $this->excludes = $excludes;
        $this->extensions = \array_map(function ($extension) {
            return \sprintf('*.%s', \ltrim($extension, '.'));
        }, $extensions);
        $this->warning = $warning;
    }

    /**
     * Check the files.
     *
     * @param SplFileInfo[] $files
     * @param bool          $cache
     *
     * @return array
     */
    public function lint($files = [], $cache = true)
    {
        if (empty($files)) {
            $files = $this->getFiles();
        }

        $processCallback = is_callable($this->processCallback) ? $this->processCallback : function () {
        };

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

    /**
     * Cache setter.
     *
     * @param array $cache
     */
    public function setCache($cache = [])
    {
        if (is_array($cache)) {
            $this->cache = $cache;
        } else {
            $this->cache = [];
        }
    }

    /**
     * Fetch files.
     *
     * @return SplFileInfo[]
     */
    public function getFiles()
    {
        if (empty($this->files)) {
            foreach ($this->path as $path) {
                if (is_dir($path)) {
                    $this->files = array_merge($this->files, $this->getFilesFromDir($path));
                } elseif (is_file($path)) {
                    $this->files[$path] = new SplFileInfo($path, $path, $path);
                }
            }
        }

        return $this->files;
    }

    /**
     * Get files from directory.
     *
     * @param string $dir
     *
     * @return SplFileInfo[]
     */
    protected function getFilesFromDir($dir)
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

    /**
     * Set Files.
     *
     * @param string[] $files
     *
     * @return \Overtrue\PHPLint\Linter
     */
    public function setFiles(array $files)
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

    /**
     * Set process callback.
     *
     * @param callable $processCallback
     *
     * @return Linter
     */
    public function setProcessCallback($processCallback)
    {
        $this->processCallback = $processCallback;

        return $this;
    }

    /**
     * Set process limit.
     *
     * @param int $processLimit
     *
     * @return \Overtrue\PHPLint\Linter
     */
    public function setProcessLimit($processLimit)
    {
        $this->processLimit = $processLimit;

        return $this;
    }

    /**
     * @param string $filename
     *
     * @return mixed
     */
    protected function createLintProcess($filename)
    {
        $command = [
            PHP_SAPI == 'cli' ? PHP_BINARY : PHP_BINDIR . '/php',
            '-d error_reporting=E_ALL',
            '-d display_errors=On',
            '-l', $filename,
        ];

        return new Lint($command);
    }
}
