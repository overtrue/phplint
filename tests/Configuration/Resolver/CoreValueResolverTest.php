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

use Overtrue\PHPLint\Configuration\Resolver\CoreValueResolver;
use Overtrue\PHPLint\Configuration\Resolver\ValueResolverInterface;
use Overtrue\PHPLint\Console\Attribute\ReflectionMember;
use Overtrue\PHPLint\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

use function iterator_to_array;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
#[CoversClass(CoreValueResolver::class)]
final class CoreValueResolverTest extends TestCase
{
    /** @var ReflectionParameter[] $parameters  */
    private array $parameters;

    private ValueResolverInterface $resolver;

    private InputInterface $input;
    private OutputInterface $output;

    /**
     * @throws ReflectionException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $command = new class {
            public function __invoke(
                InputInterface $input,
                OutputInterface $output,
                Application $application,
            ) {
            }
        };

        $reflection = new ReflectionMethod($command, '__invoke');
        $this->parameters = $reflection->getParameters();

        $this->output = new NullOutput();
        $this->input = new ArrayInput(['output' => $this->output, 'application' => $this->application]);

        $this->resolver = new CoreValueResolver($this->application, $this->output);
    }

    public function testInputDependencyInjectionUsage(): void
    {
        $expected = [$this->input];

        $member = new ReflectionMember($this->parameters[0]);

        $resolved = $this->resolver->resolve('input', $this->input, $member);

        $this->assertSame(
            $expected,
            iterator_to_array($resolved),
            '',
        );
    }

    public function testOutputDependencyInjectionUsage(): void
    {
        $expected = [$this->output];

        $member = new ReflectionMember($this->parameters[1]);

        $resolved = $this->resolver->resolve('output', $this->input, $member);

        $this->assertSame(
            $expected,
            iterator_to_array($resolved),
            '',
        );
    }

    public function testApplicationDependencyInjectionUsage(): void
    {
        $expected = [$this->application];

        $member = new ReflectionMember($this->parameters[2]);

        $resolved = $this->resolver->resolve('application', $this->input, $member);

        $this->assertSame(
            $expected,
            iterator_to_array($resolved),
            '',
        );
    }
}
