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

namespace Overtrue\PHPLint\Tests\Configuration\Resolver;

use Overtrue\PHPLint\Configuration\Resolver\MetadataValueResolver;
use Overtrue\PHPLint\Metadata\Metadata;
use Overtrue\PHPLint\Metadata\MetadataCollection;
use Overtrue\PHPLint\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;
use Symfony\Component\Console\Attribute\Reflection\ReflectionMember;
use Symfony\Component\Console\Input\ArrayInput;

use function iterator_to_array;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
#[CoversClass(MetadataValueResolver::class)]
final class MetadataValueResolverTest extends TestCase
{
    /** @var ReflectionParameter[] $parameters  */
    private array $parameters;

    /**
     * @throws ReflectionException
     */
    protected function setUp(): void
    {
        $command = new class {
            public function __invoke(
                MetadataCollection $metadataCollection,
            ) {
            }
        };

        $reflection = new ReflectionMethod($command, '__invoke');
        $this->parameters = $reflection->getParameters();
    }

    public function testEmptyMetadataCollection(): void
    {
        $metadataCollection = new MetadataCollection();

        $expected = [$metadataCollection];

        $input = new ArrayInput(['metadataCollection' => $metadataCollection]);

        $resolver = new MetadataValueResolver($metadataCollection);

        $member = new ReflectionMember($this->parameters[0]);

        $resolved = $resolver->resolve('metadataCollection', $input, $member);

        $this->assertArraysHaveEqualValues(
            $expected,
            iterator_to_array($resolved),
            ''
        );
    }

    public function testNotEmptyMetadataCollection(): void
    {
        $metadataCollection = new MetadataCollection(Metadata::applicationVersion());

        $expected = [$metadataCollection];

        $input = new ArrayInput(['metadataCollection' => $metadataCollection]);

        $resolver = new MetadataValueResolver($metadataCollection);

        $member = new ReflectionMember($this->parameters[0]);

        $resolved = $resolver->resolve('metadataCollection', $input, $member);

        $this->assertArraysHaveEqualValues(
            $expected,
            iterator_to_array($resolved),
            ''
        );
    }
}
