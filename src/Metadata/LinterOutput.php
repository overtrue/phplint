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

namespace Overtrue\PHPLint\Metadata;

use Countable;
use Symfony\Component\Finder\Finder;
use function array_merge;
use function count;
use function json_encode;

use const JSON_UNESCAPED_SLASHES;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
final class LinterOutput extends Metadata implements Countable
{
    public const METADATA_ID = 'linter_results';

    private array $errors;
    private array $warnings;
    private array $hits;
    private array $misses;
    private int $processCount;

    public function __construct(array $results, private readonly Finder $finder)
    {
        $this->errors = $results['errors'] ?? [];
        $this->warnings = $results['warnings'] ?? [];
        $this->hits = $results['hits'] ?? [];
        $this->misses = $results['misses'] ?? [];
        $this->processCount = $results['process_count'] ?? 0;

        $this->description = 'Linter analysis results';
        $this->value = json_encode($results, JSON_UNESCAPED_SLASHES);
    }

    public function count(): int
    {
        return count($this->hits) + count($this->misses);
    }

    public function getFinder(): Finder
    {
        return $this->finder;
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

    /**
     * Returns the number of jobs used to lint all files
     */
    public function getProcessCount(): int
    {
        return $this->processCount;
    }
}
