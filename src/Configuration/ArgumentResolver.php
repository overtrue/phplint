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

namespace Overtrue\PHPLint\Configuration;

use InvalidArgumentException;
use LogicException;
use Overtrue\PHPLint\Configuration\Resolver\ConfigValueResolver;
use Overtrue\PHPLint\Configuration\Resolver\PathValueResolver;
use Overtrue\PHPLint\Configuration\Resolver\PluginValueResolver;
use Psr\Container\ContainerInterface;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionNamedType;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\ArgumentResolver\ArgumentResolverInterface;
use Symfony\Component\Console\ArgumentResolver\Exception\NearMissValueResolverException;
use Symfony\Component\Console\ArgumentResolver\Exception\ResolverNotFoundException;
use Symfony\Component\Console\ArgumentResolver\ValueResolver\BuiltinTypeValueResolver;
use Symfony\Component\Console\Attribute\Reflection\ReflectionMember;
use Symfony\Component\Console\Attribute\ValueResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\RawInputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Service\ServiceProviderInterface;

use function array_keys;
use function get_debug_type;
use function implode;
use function in_array;
use function is_int;
use function sprintf;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
class ArgumentResolver implements ArgumentResolverInterface
{
    public function __construct(
        private iterable $argumentValueResolvers = [],
        private readonly ?ContainerInterface $namedResolvers = null,
    ) {
        $this->argumentValueResolvers = $argumentValueResolvers ?: static::getDefaultValueResolvers();
    }

    /**
     * @throws ReflectionException
     */
    public function getArguments(InputInterface $input, callable $command, ?ReflectionFunctionAbstract $reflector = null): array
    {
        $reflector ??= new ReflectionFunction($command(...));

        $arguments = [];

        $argumentValueResolvers = $this->argumentValueResolvers;
        $disabledResolvers = [];

        foreach ($reflector->getParameters() as $param) {
            $argumentName = $param->getName();
            $member = new ReflectionMember($param);

            $type = $member->getType();
            $typeName = $type instanceof ReflectionNamedType ? $type->getName() : null;

            if ($typeName && in_array($typeName, [
                    InputInterface::class,
                    RawInputInterface::class,
                    OutputInterface::class,
                    SymfonyStyle::class,
                    Cursor::class,
                    Application::class,
                    Command::class,
                ], true)) {
                continue;
            }

            if ($this->namedResolvers && $attributes = $member->getAttribute(ValueResolver::class)) {
                $resolverName = null;
                foreach ($attributes as $attribute) {
                    if ($attribute->disabled) {
                        $disabledResolvers[$attribute->resolver] = true;
                    } elseif ($resolverName) {
                        throw new LogicException(
                            sprintf(
                                'You can only pin one resolver per argument, but argument "$%s" of "%s()" has more.',
                                $member->getName(),
                                $member->getSourceName()
                            )
                        );
                    } else {
                        $resolverName = $attribute->resolver;
                    }
                }

                if ($resolverName) {
                    if (!$this->namedResolvers->has($resolverName)) {
                        throw new ResolverNotFoundException(
                            $resolverName,
                            $this->namedResolvers instanceof ServiceProviderInterface
                                ? array_keys($this->namedResolvers->getProvidedServices())
                                : []
                        );
                    }

                    $argumentValueResolvers = [
                        $this->namedResolvers->get($resolverName),
                    ];
                }
            }

            $valueResolverExceptions = [];

            foreach ($argumentValueResolvers as $name => $resolver) {
                if (isset($disabledResolvers[is_int($name) ? $resolver::class : $name])) {
                    continue;
                }

                try {
                    $count = 0;
                    foreach ($resolver->resolve($argumentName, $input, $member) as $argument) {
                        ++$count;
                        $arguments[] = $argument;
                    }
                } catch (NearMissValueResolverException $e) {
                    $valueResolverExceptions[] = $e;
                }

                if (1 < $count && !$member->isVariadic()) {
                    throw new InvalidArgumentException(
                        sprintf(
                            '"%s::resolve()" must yield at most one value for non-variadic arguments.',
                            get_debug_type($resolver)
                        )
                    );
                }

                if ($count) {
                    continue 2;
                }
            }

            $reasons = array_map(static fn (NearMissValueResolverException $e) => $e->getMessage(), $valueResolverExceptions);
            if (!$reasons) {
                $reasons[] = sprintf('The parameter has no #[Argument], #[Option], or #[MapInput] attribute, and its type "%s" cannot be auto-resolved.', $typeName ?? 'unknown');
                $reasons[] = 'Add an attribute to map this parameter to command input.';
            }

            throw new RuntimeException(
                sprintf(
                    'Could not resolve parameter "$%s" of command "%s".'."\n\n".
                    'Possible reasons:'."\n".
                    '  • '. implode("\n  • ", $reasons),
                    $member->getName(),
                    $member->getSourceName()
                )
            );
        }

        //\var_dump([__METHOD__ => $arguments]);
        return $arguments;
    }

    public static function getDefaultValueResolvers(): iterable
    {
        return [
            new BuiltinTypeValueResolver(),
            new PluginValueResolver(),
            new ConfigValueResolver(),
            new PathValueResolver(
                [OptionDefinition::PATH],
                [OptionDefinition::EXCLUDE],
                [
                    OptionDefinition::PATH => OptionDefinition::DEFAULT_PATH,
                    OptionDefinition::EXCLUDE => OptionDefinition::DEFAULT_EXCLUDES,
                ]
            ),
        ];
    }
}
