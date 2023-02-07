<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Output;

use Overtrue\PHPLint\Configuration\Resolver;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Finder\Finder;

use function array_merge;
use function count;

/**
 * @author Laurent Laville
 */
final class LinterOutput
{
    private Finder $finder;
    private array $context;
    private array $errors;
    private array $warnings;
    private array $hits;
    private array $misses;

    public function __construct(array $results, Finder $finder)
    {
        $this->finder = $finder;
        $this->errors = $results['errors'] ?? [];
        $this->warnings = $results['warnings'] ?? [];
        $this->hits = $results['hits'] ?? [];
        $this->misses = $results['misses'] ?? [];
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function setContext(Resolver $configResolver, float $startTime): void
    {
        $cacheHits = count($this->getHits());
        $cacheMisses = count($this->getMisses());

        $timeUsage = Helper::formatTime(microtime(true) - $startTime);
        $memUsage = Helper::formatMemory(memory_get_usage(true));
        $cacheUsage = sprintf(
            '%d hit%s, %d miss%s',
            $cacheHits,
            $cacheHits > 1 ? 's' : '',
            $cacheMisses,
            $cacheMisses > 1 ? 'es' : ''
        );

        $this->context = [
            'time_usage' => $timeUsage,
            'memory_usage' => $memUsage,
            'cache_usage' => $cacheUsage,
            'files_count' => count($this->finder),
            'options_used' => $configResolver->getOptions(),
        ];
    }

    public function hasFailures(): bool
    {
        return (!empty($this->errors) || !empty($this->warnings));
    }

    public function getFailures(): array
    {
        return array_merge($this->errors, $this->warnings);
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Returns list of errors found by PHP native linter
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    /**
     * Returns list of warnings found by PHP native linter
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Returns list of files that were not checked again (because fingerprint is same)
     */
    public function getHits(): array
    {
        return $this->hits;
    }

    /**
     * Returns list of files that were check since the last scan.
     */
    public function getMisses(): array
    {
        return $this->misses;
    }
}
