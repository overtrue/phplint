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

/**
 * Let's value resolvers tell when an argument could be under their watch but failed to be resolved.
 *
 * Throwing this exception inside `ValueResolverInterface::resolve` does not interrupt the value resolvers chain.
 *
 * Original Code from :
 * - https://github.com/symfony/console/blob/8.1/ArgumentResolver/Exception/NearMissValueResolverException.php
 *
 * @since Release 9.8.0
 */
final class NearMissValueResolverException extends RuntimeException
{
}
