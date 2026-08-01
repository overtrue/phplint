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

use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Configuration\Resolver\PathValueResolver;
use Overtrue\PHPLint\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Attribute\Reflection\ReflectionMember;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

use function dirname;
use function iterator_to_array;
use function realpath;

use const DIRECTORY_SEPARATOR;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
#[CoversClass(PathValueResolver::class)]
final class PathValueResolverTest extends TestCase
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
                #[Argument]
                ?array $sourcePath = null,
                #[Option]
                ?array $excludePath = null,
            ) {
            }
        };

        $reflection = new ReflectionMethod($command, '__invoke');
        $this->parameters = $reflection->getParameters();
    }

    public function testPathArgument(): void
    {
        $sourcePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'fixtures';

        $expected = [[realpath($sourcePath)]];

        $arguments = ['sourcePath' => $sourcePath];

        $input = new ArrayInput(
            $arguments,
            new InputDefinition([
                new InputArgument('sourcePath', InputArgument::REQUIRED),
            ])
        );

        $resolver = new PathValueResolver(
            [
                OptionDefinition::PATH,  // when #[Argument name=] defined
                'sourcePath'             // without #[Argument name=] declaration, used parameter variable name
            ],
        );

        $member = new ReflectionMember($this->parameters[0]);

        $resolved = $resolver->resolve('sourcePath', $input, $member);

        $this->assertArraysHaveEqualValues(
            $expected,
            iterator_to_array($resolved),
            ''
        );
    }

    public function testPathOption(): void
    {
        $excludes = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'vendor';

        $expected = [[$excludes]];

        $arguments = ['--exclude' => ['vendor']];

        $input = new ArrayInput(
            $arguments,
            new InputDefinition([
                new InputOption('exclude', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED),
            ])
        );

        $resolver = new PathValueResolver(
            [],
            [
                OptionDefinition::EXCLUDE,  // when #[Option name=] defined
                'excludePath'               // without #[Option name=] declaration, used parameter variable name
            ],
        );

        $member = new ReflectionMember($this->parameters[1]);

        $resolved = $resolver->resolve('exclude', $input, $member);

        $this->assertArraysHaveEqualValues(
            $expected,
            iterator_to_array($resolved),
            ''
        );
    }
}
