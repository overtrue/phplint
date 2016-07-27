<?php

/*
 * This file is part of the overtrue/phplint.
 *
 * (c) 2016 overtrue <i@overtrue.me>
 */

namespace Overtrue\PHPLint;

use Overtrue\PHPLint\Process\Lint;
use Symfony\Component\Finder\Finder;

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
     * @var array
     */
    private $files = [];

    /**
     * @var array
     */
    private $cache = [];

    /**
     * @var string
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
    private $procLimit = 5;

    /**
     * Constructor.
     *
     * @param string $path
     * @param array  $excludes
     * @param array  $extensions
     */
    public function __construct($path, array $excludes = [], array $extensions = ['php'])
    {
        $this->path = $path;
        $this->excludes = $excludes;
        $this->extensions = $extensions;
    }

    /**
     * Check the files.
     *
     * @param array $files
     *
     * @return array
     */
    public function lint($files = [])
    {
        if (empty($files)) {
            $files = $this->getFiles();
        }

        $processCallback = is_callable($this->processCallback) ? $this->processCallback : function() {
        };

        $errors = [];
        $running = [];
        $newCache = [];

        while (!empty($files) || !empty($running)) {
            for ($i = count($running); $files && $i < $this->procLimit; ++$i) {
                $file = array_shift($files);
                $filename = $file->getRealpath();

                if (!isset($this->cache[$filename]) || $this->cache[$filename] !== md5_file($filename)) {
                    $running[$filename] = new Lint(PHP_BINARY.' -l '.$filename);
                    $running[$filename]->start();
                }
            }

            foreach ($running as $filename => $lintProcess) {
                if ($lintProcess->isRunning()) {
                    continue;
                }

                unset($running[$filename]);
                if ($lintProcess->hasSyntaxError()) {
                    $processCallback('error', $filename);
                    $errors[$filename] = array_merge(['file' => $filename], $lintProcess->getSyntaxError());
                } else {
                    $newCache[$filename] = md5_file($filename);
                    $processCallback('ok', $filename);
                }
            }

            file_put_contents(__DIR__.'/../.phplint-cache', json_encode($newCache));
        }

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
     * @return array
     */
    public function getFiles()
    {
        if (empty($this->files)) {
            $this->files = new Finder();
            $this->files->files()->ignoreUnreadableDirs()->in(realpath($this->path));

            foreach ($this->excludes as $exclude) {
                $this->files->notPath($exclude);
            }

            foreach ($this->extensions as $extension) {
                $this->files->name('*.'.$extension);
            }

            $this->files = iterator_to_array($this->files);
        }

        return $this->files;
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
     * @param int $procLimit
     */
    public function setProcessLimit($procLimit)
    {
        $this->procLimit = $procLimit;

        return $this;
    }
}
