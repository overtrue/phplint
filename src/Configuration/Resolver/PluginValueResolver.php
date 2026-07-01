<?php

declare(strict_types=1);

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

use const PHP_SAPI;

class PluginValueResolver implements ValueResolverInterface
{
    public function __construct(
        private ?EnvConfigInterface $envConfig = null,
    ) {
        $this->envConfig ??= new EnvConfig('phplint');
    }

    public function resolve(string $argumentName, InputInterface $input, ReflectionMember $member): iterable
    {
        $argumentType = $member->getType()?->getName();

        if ($argumentType !== ExtensionEnum::class) {
            return [];
        }

        $env = $this->envConfig;
        $frontend = $env->get('frontend', PHP_SAPI);

        $defaultPlugin = match ($frontend) {
            'cli' => ExtensionEnum::OUTPUT_MANAGER->value,
            default => null,
        };

        $value = $input->hasOption($argumentName) ? $input->getOption($argumentName) : [];
        $value = count($value) === 0 ? $defaultPlugin : $value;

        if (!is_array($value)) {
            $value = [$value];
        }
        if (!in_array($defaultPlugin, $value, true)) {
            $value[] = $defaultPlugin;
        }

        $resolved = [];

        foreach ($value as $v) {
            $enum = $v instanceof BackedEnum ? $v : $this->resolveOption($argumentName, $v, $frontend);
            if ($enum instanceof BackedEnum && !ExtensionEnum::isAllowed($enum->value, $frontend)) {
                continue;
            }
            $resolved[] = $enum ?? null;
        }

        if (count($resolved) === 0 && count($value)) {
            $resolved[] = null;
        }

        return $resolved;
    }

    private function resolveOption(string $argumentName, ?string $value, string $frontend): ?BackedEnum
    {
        if (null === $value) {
            return null;
        }

        $suggestedValues = array_column(ExtensionEnum::allowed($frontend), 'value');

        return ExtensionEnum::tryFrom($value)
            ?? throw InvalidOptionException::fromEnumValue($argumentName, $value, $suggestedValues, $frontend);
    }
}
