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

namespace Overtrue\PHPLint\Console;

use function strtolower;
use function ucfirst;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
enum SectionEnum: string
{
    case DEFAULT = 'section';
    case ARGUMENT = 'argument';
    case CACHE = 'cache';
    case COMMAND = 'command';
    case ENVIRONMENT = 'environment';
    case EVENT = 'event';
    case METADATA = 'metadata';
    case PLUGIN = 'plugin';
    case PROFILE = 'profile';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public static function toValue(string $label): string
    {
        return strtolower($label);
    }
}
