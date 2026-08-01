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

use Overtrue\PHPLint\Configuration\Resolver\PluginValueResolver;
use Overtrue\PHPLint\Extension\ExtensionEnum;
use Overtrue\PHPLint\Extension\ExtensionEnumInterface;
use Overtrue\PHPLint\Extension\ExtensionInterface;
use Overtrue\PHPLint\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionEnum;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Attribute\Reflection\ReflectionMember;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

use function iterator_to_array;
use function putenv;

enum ExtensionTestAllowed: string implements ExtensionEnumInterface
{
    case PLUGIN_ONE = 'plugin_one';

    public static function allowed(string $frontend): array
    {
        $reflector = new ReflectionEnum(self::class);
        return $reflector->getCases();
    }

    public static function isAllowed(string $value, string $frontend): bool
    {
        $allowed = self::allowed($frontend);

        foreach ($allowed as $case) {
            if ($case->getBackingValue() === $value) {
                return true;
            }
        }

        return false;
    }
}

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
#[CoversClass(PluginValueResolver::class)]
final class PluginValueResolverTest extends TestCase
{
    /**
     * @throws ReflectionException
     */
    public function testBuiltinPluginUsage(): void
    {
        $command = new class {
            public function __invoke(
                #[Option]
                ExtensionEnum ...$extensions,
            ) {
            }
        };

        $input = new ArrayInput(
            ['--extensions' => [ExtensionEnum::OUTPUT_MANAGER->value, ExtensionEnum::PROFILE_MANAGER->value]],
            new InputDefinition([
                new InputOption('extensions', 'x', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY),
            ])
        );

        $expected = [ExtensionEnum::OUTPUT_MANAGER, ExtensionEnum::PROFILE_MANAGER];

        $reflection = new ReflectionMethod($command, '__invoke');
        $member = new ReflectionMember($reflection->getParameters()[0]);

        $resolver = new PluginValueResolver();
        $resolved = $resolver->resolve('extensions', $input, $member);

        $this->assertArraysHaveEqualValues(
            $expected,
            iterator_to_array($resolved),
            '',
        );
    }

    /**
     * @throws ReflectionException
     */
    public function testCustomPluginUsage(): void
    {
        $pluginOne = new class implements ExtensionInterface
        {
            public function initialize(ConsoleCommandEvent $event): void
            {
            }

            public function getName(): string
            {
                return ExtensionTestAllowed::PLUGIN_ONE->value;
            }

            public static function getDefinition(): InputDefinition
            {
                // this plugin does not provide any additional option to the lint command
                return new InputDefinition();
            }
        };

        $command = new class {
            public function __invoke(
                #[Option]
                ExtensionTestAllowed ...$extensions,
            ) {
            }
        };

        $input = new ArrayInput(
            ['--extensions' => [$pluginOne->getName()]],
            new InputDefinition([
                new InputOption('extensions', 'x', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY),
            ])
        );

        putenv("PLINT_FRONTEND=ci");   // should be different from SAPI name (@link PHP_SAPI)

        $expected = [ExtensionTestAllowed::PLUGIN_ONE];

        $reflection = new ReflectionMethod($command, '__invoke');
        $member = new ReflectionMember($reflection->getParameters()[0]);

        $resolver = new PluginValueResolver(null, ExtensionTestAllowed::class);
        $resolved = $resolver->resolve('extensions', $input, $member);

        $this->assertArraysHaveEqualValues(
            $expected,
            iterator_to_array($resolved),
            '',
        );
    }
}
