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

use Overtrue\PHPLint\Metadata\Metadata;
use Overtrue\PHPLint\Metadata\MetadataCollection;
use Symfony\Component\Console\Attribute\Reflection\ReflectionMember;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
class MetadataValueResolver implements ValueResolverInterface
{
    public function __construct(
        protected ?MetadataCollection $metadataCollection = null,
    ) {
        $this->metadataCollection ??= new MetadataCollection(
            Metadata::applicationVersion(),
        );
    }

    public function resolve(string $argumentName, InputInterface $input, ReflectionMember $member): iterable
    {
        $argumentType = $member->getType()?->getName();

        if ($argumentType !== MetadataCollection::class) {
            return [];
        }

        $value = $input->hasArgument($argumentName) ? $input->getArgument($argumentName) : $this->metadataCollection;

        return [$value];
    }
}
