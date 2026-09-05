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

use Countable;
use LogicException;
use Overtrue\PHPLint\Configuration\Resolver;
use Overtrue\PHPLint\Event\AfterCheckingEvent;
use Overtrue\PHPLint\Event\AfterLintFileEvent;
use Overtrue\PHPLint\Event\BeforeCheckingEvent;
use Overtrue\PHPLint\Event\BeforeLintFileEvent;
use Overtrue\PHPLint\Event\Events;
use Overtrue\PHPLint\Extension\ProfileManager;
use Overtrue\PHPLint\Helper\ProcessHelper;
use Overtrue\PHPLint\Metadata\Metadata;
use Overtrue\PHPLint\Metadata\MetadataCollection;
use Overtrue\PHPLint\Metadata\ProfilerOutput;
use Overtrue\PHPLint\Output\LinterOutput;
use Overtrue\PHPLint\Process\LintProcess;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Throwable;

use function array_chunk;
use function array_push;
use function count;
use function md5_file;
use function phpversion;
use function version_compare;

/**
 * @author Overtrue
 * @author Laurent Laville (code-rewrites since v9.0)
 */
final class Linter implements LoggerAwareInterface, Countable
{
    use LoggerAwareTrait;

    private array $results;

    private EventDispatcherInterface $dispatcher;

    private Cache $cache;

    private ?Stopwatch $stopwatch = null;

    private \Overtrue\PHPLint\Metadata\LinterOutput $finalResults;

    public function __construct(
        private readonly ?Resolver $configResolver = null,  // @deprecated keep only for API compatibility with previous version 9.7.x
                                                            // will be removed in next API version
        ?EventDispatcherInterface $dispatcher = null,
        private readonly ?Application $client = null,   // @deprecated keep only for API compatibility with previous version 9.7.x
                                                        // will be removed in next API version
        private readonly ?HelperSet $helperSet = null,
        private readonly ?OutputInterface $output = null,
        ?Cache $cache = null,
        private readonly int $processLimit = 1,
        private readonly bool $dryRun = false,
        private readonly bool $showWarning = false,
        private readonly int $memoryLimit = -1,
    ) {
        $this->dispatcher = $dispatcher ?? new EventDispatcher();
        $this->cache = $cache ?? new Cache();

        $this->results = [
            'errors' => [],
            'warnings' => [],
            'hits' => [],
            'misses' => [],
            'process_count' => 0,
        ];

        $this->finalResults = Metadata::linterResults($this->results, new Finder());
    }

    /**
     * @throws Throwable
     */
    public function lintFiles(
        Finder $finder,
        ?float $startTime = null,  // @deprecated since release 9.8.0, and will be removed in next API version
        ?MetadataCollection $metadataCollection = null
    ): LinterOutput {
        $metadataCollection = $metadataCollection ?? new MetadataCollection();

        $profiling = $metadataCollection->getMetadata(ProfilerOutput::class);
        $this->stopwatch = $profiling?->getStopwatch();

        try {
            $fileCount = count($finder);
        } catch (LogicException) {
            $fileCount = 0;
        }

        $this->dispatcher->dispatch(
            new BeforeCheckingEvent(
                $this,
                [
                    'fileCount' => $fileCount,  // @deprecated entry, use next entry instead
                    BeforeCheckingEvent::FILE_COUNT => $fileCount,
                ]
            ),
            Events::BEFORE_CHECKING,
        );

        $processCount = 0;
        if ($fileCount > 0) {
            $results = $this->doLint($finder, $processCount);
        } else {
            $results = [];
        }

        // adds the cache analysis results
        $metadataCollection->add(Metadata::cacheResults($this->cache));

        $this->cache->prune();

        // adds the source code analysis results
        $finalResults = Metadata::linterResults($results, $finder);
        $metadataCollection->add($finalResults);

        $this->dispatcher->dispatch(
            new AfterCheckingEvent(
                $this,
                // only to keep compatibility with previous API 9.7 version
                [AfterCheckingEvent::ANALYSIS_RESULTS => $finalResults]
            ),
            Events::AFTER_CHECKING,
        );

        // Only to keep API Backward Compatible with version 9.7.x
        // Will be removed in next API version
        $results = [
            'errors' => $finalResults->getErrors(),
            'warnings' => $finalResults->getWarnings(),
            'hits' => $finalResults->getHits(),
            'misses' => $finalResults->getMisses(),
        ];
        $this->finalResults = $finalResults;
        return new LinterOutput($results, $finder);
    }

    public function count(): int
    {
        return count($this->finalResults);
    }

    public function hasFailures(): bool
    {
        return $this->finalResults->hasFailures();
    }

    /**
     * @throws InvalidArgumentException
     */
    private function doLint(Finder $finder, int &$processCount): array
    {
        $iterator = $finder->getIterator();

        while ($iterator->valid()) {
            $fileInfo = $iterator->current();
            if ($this->cache->isHit($fileInfo->getRealPath())) {
                $this->results['hits'][] = $fileInfo;
            } else {
                $this->results['misses'][] = $fileInfo;
            }

            $iterator->next();
        }
        unset($iterator);

        /**
         * Since php 8.3 the `php -l` commandline supports passing multiple file paths at once.
         * @link https://github.com/overtrue/phplint/issues/197
         */
        $chunkSize = version_compare(phpversion(), '8.3', 'lt') ? 1 : $this->processLimit;
        $chunks = array_chunk($this->results['misses'], $chunkSize);

        $this->results['process_count'] = count($chunks);

        if ($this->dryRun) {
            foreach ($chunks as $index => $chunk) {
                foreach ($chunk as $fileInfo) {
                    $this->logger->info(
                        '{filename} ... queued on process #{process_id}',
                        ['filename' => $fileInfo->getRelativePathname(), 'process_id' => $index + 1]
                    );
                }
            }

            return $this->results;
        }

        $processRunning = [];

        /** @var ?ProcessHelper $helper */
        $helper = $this->helperSet?->has('process') ? $this->helperSet?->get('process') : null;  // @phpstan-ignore-line

        foreach ($chunks as $loop => $files) {
            $this->stopwatch?->lap(ProfileManager::LINT_FILES_EVENT);
            $lintProcess = $this->createLintProcess($files)
                ->setHelper($helper)
                ->setOutput($this->output)
            ;
            $lintProcess->begin();

            // enqueue lint process as much as authorized by --jobs option (number of paralleled jobs to run) and/or CPU available
            ++$processCount;
            $processRunning[$processCount] = $lintProcess;

            while (count($processRunning) >= $this->processLimit || (!empty($processRunning) && $loop === count($chunks) - 1)) {
                $this->checkProcessRunning($processRunning);
            }
        }

        $this->results['process_count'] = $processCount;

        return $this->results;
    }

    /**
     * @param array<int, LintProcess> $processRunning
     * @throws InvalidArgumentException
     */
    private function checkProcessRunning(array &$processRunning): void
    {
        foreach ($processRunning as $pid => $lintProcess) {
            if (!$lintProcess->isFinished()) {
                // php lint process is still running in background, wait until it's finished
                continue;
            }
            unset($processRunning[$pid]);

            // checks status of all files linked at end of the php lint process
            foreach ($lintProcess->getFiles() as $fileInfo) {
                $this->dispatcher->dispatch(
                    new BeforeLintFileEvent(
                        $this,
                        [
                            BeforeLintFileEvent::FILENAME => $fileInfo->getRelativePathname(),
                            BeforeLintFileEvent::FILE_INFO => $fileInfo,
                        ]
                    ),
                    Events::BEFORE_LINT_FILE,
                );

                $status = $this->processFile($fileInfo, $lintProcess);

                $this->dispatcher->dispatch(
                    new AfterLintFileEvent(
                        $this,
                        [
                            AfterLintFileEvent::FILENAME => $fileInfo->getRelativePathname(),
                            AfterLintFileEvent::FILE_INFO => $fileInfo,
                            AfterLintFileEvent::FILE_STATUS => $status,
                        ]
                    ),
                    Events::AFTER_LINT_FILE,
                );
            }
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function processFile(SplFileInfo $fileInfo, LintProcess $lintProcess): string
    {
        $filename = $fileInfo->getRealPath();

        $item = $lintProcess->getItem($fileInfo);

        if ($item->hasSyntaxError()) {
            $status = 'error';
        } elseif ($this->showWarning && $item->hasSyntaxWarning()) {
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
            PHP_SAPI === 'cli' ? PHP_BINARY : PHP_BINDIR . '/php',
            '-d error_reporting=E_ALL',
            '-d display_errors=On',
        ];

        if ($this->memoryLimit > 0) {
            $command[] = '-d memory_limit=' . $this->memoryLimit;
        }

        $command[] = '-l';
        $command[] = '-n';
        array_push($command, ...$files);

        return (new LintProcess($command))->setFiles($files);
    }
}
