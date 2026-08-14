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

use RuntimeException;

use function count;
use function implode;
use function sprintf;

/**
 * Original Code from :
 * - https://github.com/symfony/console/blob/8.1/ArgumentResolver/Exception/ResolverNotFoundException.php
 *
 * @since Release 9.8.0
 */
final class ResolverNotFoundException extends RuntimeException
{
    /**
     * @param string[] $alternatives
     */
    public function __construct(string $name, array $alternatives = [])
    {
        $msg = sprintf('You have requested a non-existent resolver "%s".', $name);
        if ($alternatives) {
            if (1 === count($alternatives)) {
                $msg .= ' Did you mean this: "';
            } else {
                $msg .= ' Did you mean one of these: "';
            }
            $msg .= implode('", "', $alternatives) . '"?';
        }

        parent::__construct($msg);
    }
}
