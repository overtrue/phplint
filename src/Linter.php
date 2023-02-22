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

use function count;
use function md5_file;
use function microtime;
use function trim;

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

    public function __construct(Resolver $configResolver, EventDispatcherInterface $dispatcher, string $appVersion = '9.0.x-dev')
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
        //$logger = new Logger();
        $this->cache = new Cache($adapter); //, $logger);

        $this->results = [
            'errors' => [],
            'warnings' => [],
            'hits' => [],
            'misses' => [],
        ];
    }

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

        if ($fileCount > 0) {
            $pid = 0;
            $processRunning = [];
            $iterator = $finder->getIterator();

            while ($iterator->valid() || !empty($processRunning)) {
                for ($i = count($processRunning); $iterator->valid() && $i < $this->processLimit; ++$i) {
                    $fileInfo = $iterator->current();
                    $this->dispatcher->dispatch(new BeforeLintFileEvent($this, ['file' => $fileInfo]));
                    $filename = $fileInfo->getRealPath();

                    if ($this->cache->isHit($filename)) {
                        $this->results['hits'][] = $filename;
                    } else {
                        $lintProcess = $this->createLintProcess($filename);
                        $lintProcess->start();

                        ++$pid;
                        $processRunning[$pid] = [
                            'process' => $lintProcess,
                            'file' => $fileInfo,
                        ];
                        $this->results['misses'][] = $filename;
                    }

                    $iterator->next();
                }

                foreach ($processRunning as $pid => $item) {
                    /** @var LintProcess $lintProcess */
                    $lintProcess = $item['process'];
                    if ($lintProcess->isRunning()) {
                        continue;
                    }
                    /** @var SplFileInfo $fileInfo */
                    $fileInfo = $item['file'];
                    $status = $this->processFile($fileInfo, $lintProcess);

                    unset($processRunning[$pid]);
                    $this->dispatcher->dispatch(new AfterLintFileEvent($this, ['file' => $fileInfo, 'status' => $status]));
                }
            }

            $results = $this->results;
        } else {
            $results = [];
        }
        $finalResults = new LinterOutput($results, $finder);
        $finalResults->setContext($this->configResolver, $startTime);

        $this->dispatcher->dispatch(new AfterCheckingEvent($this, ['results' => $finalResults]));

        return $finalResults;
    }

    private function processFile(SplFileInfo $fileInfo, LintProcess $lintProcess): string
    {
        $filename = $fileInfo->getRealPath();

        $output = trim($lintProcess->getOutput());
        $item = $lintProcess->getItem($output);

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
                'relative_file' => $fileInfo->getRelativePathname(),
                'error' => $item->getMessage(),
                'line' => $item->getLine(),
            ];
        }

        return $status;
    }

    private function createLintProcess(string $filename): LintProcess
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

        return new LintProcess($command);
    }
}
