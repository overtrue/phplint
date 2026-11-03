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

use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Input\InputInterface;

use function ini_get;
use function ini_parse_quantity;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
class MemoryLimitValueResolver implements ValueResolverInterface
{
    public function resolve(string $argumentName, InputInterface $input, ReflectionMember $member): iterable
    {
        $argumentType = $member->getType()?->getName(); // @phpstan-ignore method.notFound

        if ($argumentType !== 'int') {
            return [];
        }

        $argumentAttributes = $member->getAttribute(Option::class);
        // retrieve the argument name defined by the #[Option(name:)] attribute, or fallback to PHP variable name
        $argumentName = $argumentAttributes?->name ?: $argumentName;

        $memoryLimit = $input->hasOption($argumentName) ? $input->getOption($argumentName) : null;

        if (null === $memoryLimit) {
            $memoryLimit = ini_get('memory_limit');
        }

        $value = ini_parse_quantity($memoryLimit);

        return [$value];
    }
}
