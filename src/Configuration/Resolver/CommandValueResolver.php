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

use Overtrue\PHPLint\Configuration\Exception\ResolverNotFoundException;
use Overtrue\PHPLint\Console\Attribute\MapInput;
use Overtrue\PHPLint\Console\Attribute\ReflectionMember;
use Overtrue\PHPLint\Console\Attribute\ValueResolver;

use Psr\Container\ContainerInterface;

use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Input\InputInterface;

use ReflectionException;
use ReflectionProperty;

use function in_array;
use function iterator_count;
use function iterator_to_array;

/**
 * Code inspired from https://github.com/symfony/console/blob/8.1/ArgumentResolver/ValueResolver/MapInputValueResolver.php
 *
 * @author Laurent Laville
 * @since Release 9.8.1
 */
readonly class CommandValueResolver implements ValueResolverInterface
{
    public function __construct(
        private ContainerInterface $namedResolvers,
        private array $defaultConfiguration = [],
    ) {
    }

    /**
     * @throws ReflectionException
     */
    public function resolve(string $argumentName, InputInterface $input, ReflectionMember $member): iterable
    {
        if (!$attribute = MapInput::tryFrom($member->getMember())) {
            return [];
        }

        $instance = $this->resolveMapInput($attribute, $input);

        return [$instance];
    }

    /**
     * @throws ReflectionException
     */
    private function resolveMapInput(MapInput $mapInput, InputInterface $input): object
    {
        $instance = $mapInput->getClass()->newInstanceWithoutConstructor();

        foreach ($mapInput->getDefinition() as $name => $spec) {
            // ignore required arguments that are not set yet (may happen in interactive mode)
            if ($spec instanceof Argument && $spec->isRequired() && in_array($input->getArgument($spec->name), [null, []], true)) {
                continue;
            }

            $defaultValue = $mapInput->getClass()->getProperty($name)->getDefaultValue()
                ?? $this->defaultConfiguration[$spec->name]
                ?? null
            ;

            if ($spec instanceof Argument) {
                if (empty($input->getArgument($spec->name))) {
                    $input->setArgument($spec->name, $defaultValue);
                }
            }

            if ($spec instanceof Option) {
                if (empty($input->getOption($spec->name))) {
                    $input->setOption($spec->name, $defaultValue);
                }
            }

            $propertyValue = match (true) {
                $spec instanceof Argument, $spec instanceof Option => $this->resolveSpec($mapInput->getClass()->getProperty($name), $input),
                $spec instanceof MapInput => $this->resolveMapInput($spec, $input),
            };

            $instance->$name = $propertyValue;
        }

        return $instance;
    }

    private function resolveSpec(ReflectionProperty $property, InputInterface $input): mixed
    {
        $member = new ReflectionMember($property);
        $valuesResolvers = $member->getAttributes(ValueResolver::class);

        $argumentName = $property->getName();

        foreach ($valuesResolvers as $attribute) {
            if ($attribute->disabled) {
                continue;
            }
            $resolverName = $attribute->resolver;

            if (!$this->namedResolvers->has($resolverName)) {
                throw new ResolverNotFoundException($resolverName);
            }

            $resolver = $this->namedResolvers->get($resolverName);

            $resolved = $resolver->resolve($argumentName, $input, $member);

            if (iterator_count($resolved) > 0) {
                return iterator_to_array($resolved)[0];
            };
        }

        return null;
    }
}
