<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Configuration\Resolver;

use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Attribute\Reflection\ReflectionMember;
use Symfony\Component\Console\Input\InputInterface;

use function array_map;
use function in_array;

class PathValueResolver implements ValueResolverInterface
{
    public function __construct(
        protected array $argumentNamesAllowed = [],
        protected array $optionNamesAllowed = [],
        protected array $defaultValues = [],
    ) {
    }

    public function resolve(string $argumentName, InputInterface $input, ReflectionMember $member): iterable
    {
        $argumentType = $member->getType()?->getName();

        if ($argumentType !== 'array') {
            return [];
        }

        $argumentAttributes = $member->getAttribute(Argument::class);
        // retrieve the argument name defined by the #[Argument(name:)] attribute, or fallback to PHP variable name
        $argumentName = $argumentAttributes?->name ? : $argumentName;

        if (!in_array($argumentName, $this->argumentNamesAllowed, true)) {
            $argumentAttributes = $member->getAttribute(Option::class);
            // retrieve the argument name defined by the #[Option(name:)] attribute, or fallback to PHP variable name
            $argumentName = $argumentAttributes?->name ? : $argumentName;

            if (!in_array($argumentName, $this->optionNamesAllowed, true)) {
                return [];
            }
        }

        $values = $input->hasArgument($argumentName)
            ? $input->getArgument($argumentName)
            : ($input->hasOption($argumentName) ? $input->getOption($argumentName) : ($this->defaultValues[$argumentName] ?? ''));
        ;

        if (!is_array($values)) {
            $values = [$values];
        }

        $resolved = [
            array_map('realpath', $values)
        ];

//        \var_dump([$argumentName, $member->getName(), $argumentType, $argumentAttributes, $resolved]);
        return $resolved;
    }
}
