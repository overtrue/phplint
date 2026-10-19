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

use function array_unique;
use function array_values;
use function count;
use function json_encode;

use const JSON_UNESCAPED_SLASHES;

/**
 * Metadata that identify all deprecated features used by User.
 * That will allow easy notification for a smooth migration.
 *
 * @author Laurent Laville
 * @since Release 9.8.0
 */
final class DeprecatedFeatures extends Metadata implements Countable
{
    public const METADATA_ID = 'deprecated_features';

    public function __construct(private array $deprecations)
    {
        $this->description = 'Deprecations raised by features';
        $this->getDeprecations();
    }

    public function count(): int
    {
        return count($this->getDeprecations());
    }

    public function getDeprecations(): array
    {
        $deprecations = array_values(array_unique($this->deprecations));
        $this->value = json_encode($deprecations, JSON_UNESCAPED_SLASHES);
        return $deprecations;
    }

    public function add(string $message): void
    {
        $this->deprecations[] = $message;
    }
}
