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

use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Console\Attribute\ReflectionMember;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
class OutputFormatResolver implements ValueResolverInterface
{
    public function __construct(
        protected array $defaultValues = [],
    ) {
    }

    public function resolve(string $argumentName, InputInterface $input, ReflectionMember $member): iterable
    {
        $argumentType = $member->getType()?->getName();

        if ($argumentType !== 'array') {
            return [];
        }

        $argumentAttributes = $member->getAttribute(Option::class);
        // retrieve the argument name defined by the #[Option(name:)] attribute, or fallback to PHP variable name
        $argumentName = $argumentAttributes?->name ? : $argumentName;

        $values = $input->hasOption($argumentName) ? $input->getOption($argumentName) : '';

        if (empty($values)) {
            $values = $this->defaultValues[$argumentName];
        }

        if (!is_array($values)) {
            $values = [$values];
        }

        return [$values];
    }
}
