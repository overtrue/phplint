<?php

declare(strict_types=1);

namespace Overtrue\PHPLint;

use Overtrue\PHPLint\Configuration\ConfigResolver;
use Overtrue\PHPLint\Event\AfterCheckingEvent;
use Overtrue\PHPLint\Event\AfterLintFileEvent;
use Overtrue\PHPLint\Event\BeforeCheckingEvent;
use Overtrue\PHPLint\Event\BeforeLintFileEvent;
use Overtrue\PHPLint\Process\LintProcess;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Console\Application;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use function array_merge;
use function count;

final class Linter
{
    private ?EventDispatcherInterface $dispatcher;

    private Cache $cache;
    private array $errors = [];
    private int $processLimit;
    private string $memoryLimit;
    private bool $warning;
    private array $options;
    private string $appLongVersion;

    public function __construct(Application $application, array $options)
    {
        $this->dispatcher = $application->getDispatcher();
        $this->appLongVersion = $application->getLongVersion();
        $this->options = $options;
        $this->processLimit = $options[ConfigResolver::OPTION_JOBS];
        $this->memoryLimit = $options[ConfigResolver::OPTION_MEMORY_LIMIT];
        $this->warning = $options[ConfigResolver::OPTION_WARNING];

        if ($options[ConfigResolver::OPTION_NO_CACHE]) {
            $adapter = new NullAdapter();
        } else {
            $adapter = new FilesystemAdapter('paths', 0, $options[ConfigResolver::OPTION_CACHE]);
        }
        //$logger = new Logger();
        $this->cache = new Cache($adapter); //, $logger);
    }

    public function lintFiles(Finder $finder, int &$cacheHits, int &$cacheMisses): array
    {
        $this->dispatcher?->dispatch(
            new BeforeCheckingEvent(
                $this,
                ['fileCount' => count($finder), 'appVersion' => $this->appLongVersion, 'options' => $this->options]
            )
        );

        $pid = 0;
        $processRunning = [];
        $iterator = $finder->getIterator();

        while ($iterator->valid() || !empty($processRunning)) {
            for ($i = count($processRunning); $iterator->valid() && $i < $this->processLimit; ++$i) {
                $fileInfo = $iterator->current();
                $this->dispatcher?->dispatch(new BeforeLintFileEvent($this, ['file' => $fileInfo]));
                $filename = $fileInfo->getRealPath();

                if ($this->cache->isFresh($filename)) {
                    ++$cacheHits;
                } else {
                    $lintProcess = $this->createLintProcess($filename);
                    $lintProcess->start();

                    ++$pid;
                    $processRunning[$pid] = [
                        'process' => $lintProcess,
                        'file' => $fileInfo,
                    ];
                    ++$cacheMisses;
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
                $this->dispatcher?->dispatch(new AfterLintFileEvent($this, ['file' => $fileInfo, 'status' => $status]));
            }
        }

        $this->dispatcher?->dispatch(new AfterCheckingEvent($this));

        return $this->errors;
    }

    private function processFile(SplFileInfo $fileInfo, LintProcess $lintProcess): string
    {
        $filename = $fileInfo->getRealPath();

        if ($lintProcess->hasSyntaxError()) {
            $this->errors[$filename] = array_merge(
                ['absolute_file' => $filename, 'relative_file' => $fileInfo->getRelativePathname()],
                $lintProcess->getSyntaxError()
            );
            $status = 'error';
        } elseif ($this->warning && $lintProcess->hasSyntaxIssue()) {
            $this->errors[$filename] = array_merge(
                ['absolute_file' => $filename, 'relative_file' => $fileInfo->getRelativePathname()],
                $lintProcess->getSyntaxIssue()
            );
            $status = 'warning';
        } else {
            $status = 'ok';
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
