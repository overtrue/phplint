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

namespace Overtrue\PHPLint\Console\Attribute;

use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Exception\LogicException;

use Attribute;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;

use function class_exists;
use function sprintf;

/**
 * Maps a command input into an object (DTO).
 *
 * Code inspired from https://github.com/symfony/console/blob/8.1/Attribute/MapInput.php
 *
 * @since Release 9.8.1
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final class MapInput
{
    /**
     * @var array<string, Argument|Option|self>
     */
    private array $definition = [];

    private ReflectionClass $class;

    /**
     * @internal
     */
    public static function tryFrom(ReflectionParameter|ReflectionProperty $member): ?self
    {
        $reflection = new ReflectionMember($member);

        if (!$self = $reflection->getAttribute(self::class)) {
            return null;
        }

        $type = $reflection->getType();

        if (!$type instanceof ReflectionNamedType) {
            throw new LogicException(
                sprintf('The input %s "%s" must have a named type.', $reflection->getMemberName(), $member->name)
            );
        }

        if (!class_exists($class = $type->getName())) {
            throw new LogicException(sprintf('The input class "%s" does not exist.', $type->getName()));
        }

        $self->class = new ReflectionClass($class);

        foreach ($self->class->getProperties() as $property) {
            if ($argument = Argument::tryFrom($property)) {
                $self->definition[$property->name] = $argument;
            } elseif ($option = Option::tryFrom($property)) {
                $self->definition[$property->name] = $option;
            } elseif ($input = self::tryFrom($property)) {
                $self->definition[$property->name] = $input;
            }

            if (isset($self->definition[$property->name]) && (!$property->isPublic() || $property->isStatic())) {
                throw new LogicException(
                    sprintf('The input property "%s::$%s" must be public and non-static.', $self->class->name, $property->name)
                );
            }
        }

        if (!$self->definition) {
            throw new LogicException(
                sprintf('The input class "%s" must have at least one argument or option.', $self->class->name)
            );
        }

        return $self;
    }

    /**
     * @return iterable<Argument>
     */
    public function getArguments(): iterable
    {
        foreach ($this->definition as $spec) {
            if ($spec instanceof Argument) {
                yield $spec;
            } elseif ($spec instanceof self) {
                yield from $spec->getArguments();
            }
        }
    }

    /**
     * @return iterable<Option>
     */
    public function getOptions(): iterable
    {
        foreach ($this->definition as $spec) {
            if ($spec instanceof Option) {
                yield $spec;
            } elseif ($spec instanceof self) {
                yield from $spec->getOptions();
            }
        }
    }

    /**
     * @internal
     *
     * @return ReflectionClass<object>
     */
    public function getClass(): ReflectionClass
    {
        return $this->class;
    }

    /**
     * @internal
     *
     * @return array<string, Argument|Option|self>
     */
    public function getDefinition(): array
    {
        return $this->definition;
    }
}
