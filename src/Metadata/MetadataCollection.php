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

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

use function count;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
final readonly class MetadataCollection implements Countable, IteratorAggregate
{
    /**
     * @var list<Metadata>
     */
    private array $metadata;

    public function __construct(Metadata ...$metadata)
    {
        $this->metadata = $metadata;
    }

    public function count(): int
    {
        return count($this->metadata);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->metadata);
    }
}
