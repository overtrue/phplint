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

namespace Overtrue\PHPLint\Configuration\Exception;

use Closure;
use InvalidArgumentException;
use function is_array;
use function sprintf;

/**
 * Replaces Symfony\Component\Console\Exception\InvalidOptionException on Symfony 8.1 Console component.
 *
 * Adds a frontend reference.
 *
 * @author Laurent Laville
 * @since Release 9.8.0
 */

class InvalidOptionException extends InvalidArgumentException
{
    public static function fromEnumValue(string $name, string $value, array|Closure $suggestedValues, ?string $frontend = null): self
    {
        $error = sprintf('The value "%s" is not valid for the "%s" option', $value, $name);

        if ($frontend) {
            $error .= sprintf(' on "%s" frontend', $frontend);
        }
        $error .= ".";

        if (is_array($suggestedValues)) {
            $error .= sprintf(' Supported values are "%s".', implode('", "', $suggestedValues));
        }

        return new self($error);
    }
}
