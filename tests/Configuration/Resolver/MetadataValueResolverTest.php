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
use Overtrue\PHPLint\Console\Attribute\ReflectionMember;
use Overtrue\PHPLint\Metadata\Metadata;
use Overtrue\PHPLint\Metadata\MetadataCollection;
use Overtrue\PHPLint\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;
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
        parent::setUp();

        $command = new class {
            public function __invoke(
                MetadataCollection $metadataCollection,
            ) {
            }
        };

        $reflection = new ReflectionMethod($command, '__invoke');
        $this->parameters = $reflection->getParameters();
    }

    public function testDefaultApplicationMetadata(): void
    {
        $metadataCollection = new MetadataCollection(
            Metadata::applicationVersion(),
            Metadata::configurationSettings(['mode' => 'off'])
        );

        $expected = [$metadataCollection];

        $input = new ArrayInput(['metadataCollection' => $metadataCollection]);

        $resolver = new MetadataValueResolver($this->getApplication());

        $member = new ReflectionMember($this->parameters[0]);

        $resolved = $resolver->resolve('metadataCollection', $input, $member);

        $this->assertEquals(
            $expected,
            iterator_to_array($resolved),
            ''
        );
    }
}
