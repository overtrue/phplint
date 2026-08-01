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

use BackedEnum;
use Overtrue\PHPLint\Configuration\Exception\InvalidOptionException;
use Overtrue\PHPLint\Environment\EnvConfig;
use Overtrue\PHPLint\Environment\EnvConfigInterface;
use Overtrue\PHPLint\Extension\ExtensionEnum;
use Symfony\Component\Console\Attribute\Reflection\ReflectionMember;
use Symfony\Component\Console\Input\InputInterface;

use function array_column;
use function count;
use function in_array;
use function is_array;
use function is_object;
use function method_exists;

use const PHP_SAPI;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
class PluginValueResolver implements ValueResolverInterface
{
    public function __construct(
        private ?EnvConfigInterface $envConfig = null,
        private readonly string $extensionEnumClass = ExtensionEnum::class,
    ) {
        $this->envConfig ??= new EnvConfig;
    }

    public function resolve(string $argumentName, InputInterface $input, ReflectionMember $member): iterable
    {
        $argumentType = $member->getType()?->getName();

        if ($argumentType !== $this->extensionEnumClass) {
            return [];
        }

        $env = $this->envConfig;
        $frontend = $env->get('frontend', PHP_SAPI);

        $defaultPlugin = match ($frontend) {
            'cli' => ExtensionEnum::OUTPUT_MANAGER->value,
            default => null,
        };

        $value = $input->hasOption($argumentName) ? $input->getOption($argumentName) : [];

        if (!is_array($value)) {
            $value = [$value];
        }
        if (null !== $defaultPlugin && !in_array($defaultPlugin, $value, true)) {
            $value[] = $defaultPlugin;
        }

        $resolved = [];

        foreach ($value as $v) {
            $enum = $this->resolveOption($argumentName, $v, $frontend);
            if (null === $enum) {
                continue;
            }
            if (!$this->extensionEnumClass::isAllowed($enum->value, $frontend)) {
                continue;
            }
            $resolved[] = $enum;
        }

        if (count($resolved) === 0 && count($value)) {
            $resolved[] = null;
        }

        return $resolved;
    }

    /**
     * @param null|string|object|BackedEnum $value (may be an anonymous class instance that implement the ExtensionInterface contract)
     */
    private function resolveOption(string $argumentName, mixed $value, string $frontend): ?BackedEnum
    {
        if ($value instanceof BackedEnum) {
            return $value;
        }

        if (null === $value) {
            return null;
        }

        if (is_object($value) && method_exists($value, 'getName')) {
            $value = $value->getName();
        }

        $suggestedValues = array_column($this->extensionEnumClass::allowed($frontend), 'value');

        return $this->extensionEnumClass::tryFrom($value)
            ?? throw InvalidOptionException::fromEnumValue($argumentName, $value, $suggestedValues, $frontend);
    }
}
