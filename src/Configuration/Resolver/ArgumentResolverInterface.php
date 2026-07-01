<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Configuration\Resolver;

use ReflectionFunctionAbstract;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Declare the same contract as Symfony 8.1 ArgumentResolver of the Console Component.
 *
 * @see https://symfony.com/doc/current/console/value_resolver.html
 */
interface ArgumentResolverInterface
{
    public function getArguments(InputInterface $input, callable $command, ?ReflectionFunctionAbstract $reflector = null): array;
}
