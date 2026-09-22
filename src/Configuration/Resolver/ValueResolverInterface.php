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

use Overtrue\PHPLint\Console\Attribute\ReflectionMember;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Responsible for resolving the value of a Command argument based on its
 * parameter metadata and the Command MapInput.
 *
 * Force compatibility with previous versions of Symfony Console that did not accept the ArgumentResolver
 */
interface ValueResolverInterface
{
    /**
     * Returns the possible value(s) for the argument.
     */
    public function resolve(string $argumentName, InputInterface $input, ReflectionMember $member): iterable;
}
