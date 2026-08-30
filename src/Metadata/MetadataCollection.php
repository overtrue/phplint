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
use Overtrue\PHPLint\Console\SectionEnum;
use Psr\Log\LoggerInterface;
use Traversable;

use function count;
use function get_class;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
final class MetadataCollection implements Countable, IteratorAggregate
{
    /**
     * @var list<Metadata>
     */
    private array $metadata = [];

    public function __construct(Metadata ...$metadata)
    {
        foreach ($metadata as $meta) {
            $this->metadata[get_class($meta)] = $meta;
        }
    }

    public function count(): int
    {
        return count($this->metadata);
    }

    public function add(Metadata $metadata): void
    {
        $this->metadata[get_class($metadata)] = $metadata;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->metadata);
    }

    public function getMetadata(string $id): ?Metadata
    {
        $iterator = $this->getIterator();
        return $iterator->offsetExists($id) ? $iterator->offsetGet($id) : null;
    }

    public function hasMetadata(string $id): bool
    {
        $iterator = $this->getIterator();
        return $iterator->offsetExists($id);
    }

    public function describe(
        LoggerInterface $logger,
    ): array {
        $values = [];

        foreach ($this as $metadataClass => $metadata) {
            $metadataValues = $metadata->describe('value');

            $message = sprintf(
                '<comment>%s</comment> %s',
                '"{metadataClass}" values are',
                '{metadataValues}',
            );

            $logger->debug(
                $message,
                [
                    '__section__' => SectionEnum::METADATA->label(),
                    '__style__' => SectionEnum::METADATA->value,
                    'metadataClass' => $metadataClass,
                    'metadataValues' => $metadataValues,
                ]
            );
            $values[$metadataClass] = $metadataValues;
        }

        return $values;
    }
}
