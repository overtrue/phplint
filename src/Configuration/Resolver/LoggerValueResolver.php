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

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Attribute\Reflection\ReflectionMember;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
class LoggerValueResolver implements ValueResolverInterface
{
    public function __construct(
        protected ?LoggerInterface $defaultLogger = null,
    ) {
        $this->defaultLogger ??= new NullLogger();
    }

    public function resolve(string $argumentName, InputInterface $input, ReflectionMember $member): iterable
    {
        $argumentType = $member->getType()?->getName();

        if ($argumentType !== LoggerInterface::class) {
            return [];
        }

        $value = $input->hasArgument($argumentName) ? $input->getArgument($argumentName) : $this->defaultLogger;

        return [$value];
    }
}
