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

namespace Overtrue\PHPLint;

use Fiber;
use LogicException;
use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Configuration\Resolver;
use Overtrue\PHPLint\Event\AfterCheckingEvent;
use Overtrue\PHPLint\Event\AfterLintFileEvent;
use Overtrue\PHPLint\Event\BeforeCheckingEvent;
use Overtrue\PHPLint\Event\BeforeLintFileEvent;
use Overtrue\PHPLint\Output\LinterOutput;
use Overtrue\PHPLint\Process\LintProcess;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Throwable;

use function array_push;
use function array_slice;
use function count;
use function md5_file;
use function microtime;
use function phpversion;
use function version_compare;

/**
 * @author Overtrue
 * @author Laurent Laville (code-rewrites since v9.0)
 */
final class Linter
{
    private Resolver $configResolver;
    private EventDispatcherInterface $dispatcher;
    private Cache $cache;
    private array $results;
    private int $processLimit;
    private string $memoryLimit;
    private bool $warning;
    private array $options;
    private string $appLongVersion;

    public function __construct(Resolver $configResolver, EventDispatcherInterface $dispatcher, string $appVersion = '9.1.x-dev')
    {
        $this->configResolver = $configResolver;
        $this->dispatcher = $dispatcher;
        $this->appLongVersion = $appVersion;
        $this->options = $configResolver->getOptions();
        $this->processLimit = $configResolver->getOption(OptionDefinition::JOBS);
        $this->memoryLimit = (string) $configResolver->getOption(OptionDefinition::OPTION_MEMORY_LIMIT);
        $this->warning = $configResolver->getOption(OptionDefinition::WARNING);

        if ($configResolver->getOption(OptionDefinition::NO_CACHE)) {
            $adapter = new NullAdapter();
        } else {
            $adapter = new FilesystemAdapter('paths', 0, $configResolver->getOption(OptionDefinition::CACHE));
        }
        $this->cache = new Cache($adapter);

        $this->results = [
            'errors' => [],
            'warnings' => [],
            'hits' => [],
            'misses' => [],
        ];
    }

    /**
     * @throws Throwable
     */
    public function lintFiles(Finder $finder, ?float $startTime = null): LinterOutput
    {
        if (null === $startTime) {
            $startTime = microtime(true);
        }

        try {
            $fileCount = count($finder);
        } catch (LogicException) {
            $fileCount = 0;
        }

        $this->dispatcher->dispatch(
            new BeforeCheckingEvent(
                $this,
                ['fileCount' => $fileCount, 'appVersion' => $this->appLongVersion, 'options' => $this->options]
            )
        );

        $processCount = 0;

        if ($fileCount > 0) {
            $iterator = $finder->getIterator();

            while ($iterator->valid()) {
                for ($i = 0; $iterator->valid() && $i < $this->processLimit; ++$i) {
                    $fileInfo = $iterator->current();
                    $this->dispatcher->dispatch(new BeforeLintFileEvent($this, ['file' => $fileInfo]));
                    $filename = $fileInfo->getRealPath();

                    if ($this->cache->isHit($filename)) {
                        $this->results['hits'][] = $fileInfo;
                    } else {
                        $this->results['misses'][] = $fileInfo;
                    }

                    $iterator->next();
                }

                if (version_compare(phpversion(), '8.3', 'ge')) {
                    $offset = -1 * $i;
                } else {
                    $offset = -1;
                }
                $files = array_slice($this->results['misses'], $offset, null, false);

                $fiber = new Fiber(function (array $files): void {
                    $lintProcess = $this->createLintProcess($files);
                    $lintProcess->start();
                    Fiber::suspend($lintProcess);
                });

                $lintProcess = $fiber->start($files);
                ++$processCount;

                while (!$fiber->isTerminated()) {
                    if ($lintProcess->isRunning()) {
                        // php lint process is still running in background, wait until it's finished
                        continue;
                    }

                    // checks status of all files linked at end of the php lint process
                    foreach ($files as $fileInfo) {
                        $status = $this->processFile($fileInfo, $lintProcess);

                        $this->dispatcher->dispatch(
                            new AfterLintFileEvent($this, ['file' => $fileInfo, 'status' => $status])
                        );
                    }

                    $fiber->resume();
                }
            }

            $results = $this->results;
        } else {
            $results = [];
        }
        $finalResults = new LinterOutput($results, $finder);
        $finalResults->setContext($this->configResolver, $startTime, $processCount);

        $this->dispatcher->dispatch(new AfterCheckingEvent($this, ['results' => $finalResults]));

        return $finalResults;
    }

    private function processFile(SplFileInfo $fileInfo, LintProcess $lintProcess): string
    {
        $filename = $fileInfo->getRealPath();

        $item = $lintProcess->getItem($fileInfo);

        if ($item->hasSyntaxError()) {
            $status = 'error';
        } elseif ($this->warning && $item->hasSyntaxWarning()) {
            $status = 'warning';
        } else {
            $status = 'ok';

            $item = $this->cache->getItem($filename);
            $item->set(md5_file($filename));
            $this->cache->saveItem($item);
        }

        if ($status !== 'ok') {
            $this->results[$status . 's'][$filename] = [
                'absolute_file' => $filename,
                'relative_file' => $item->getFileInfo()->getRelativePathname(),
                'error' => $item->getMessage(),
                'line' => $item->getLine(),
            ];
        }

        return $status;
    }

    private function createLintProcess(array $files): LintProcess
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
        array_push($command, ...$files);

        return (new LintProcess($command))->setFiles($files);
    }
}
