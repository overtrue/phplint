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

namespace Overtrue\PHPLint\Configuration\Resolver;

use ReflectionFunctionAbstract;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Be compatible with previous versions of Symfony Console that did not accept the ArgumentResolver
 * introduced in version 8.1
 *
 * @see https://symfony.com/doc/current/console/value_resolver.html
 *
 * @author Laurent Laville
 * @since Release 9.8.0
 */
interface ArgumentResolverInterface
{
    public function getArguments(InputInterface $input, callable $command, ?ReflectionFunctionAbstract $reflector = null): array;
}
