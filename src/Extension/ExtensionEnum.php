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

namespace Overtrue\PHPLint\Extension;

use BackedEnum;
use ReflectionEnum;
use Symfony\Component\String\UnicodeString;

use function class_exists;
use function in_array;
use function is_string;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
enum ExtensionEnum: string implements ExtensionEnumInterface
{
    case CACHE_MANAGER = 'cache_manager';
    case DIAGNOSE_MANAGER = 'diagnose_manager';
    case OUTPUT_MANAGER = 'output_manager';
    case PROFILE_MANAGER = 'profile_manager';
    case PROGRESS_MANAGER = 'progress_manager';

    public static function factory(BackedEnum|string|null $value): ?ExtensionInterface
    {
        if (null === $value) {
            return null;
        }

        $manager = is_string($value) ? $value : $value->value;

        $className = (new UnicodeString($manager))->pascal()->toString();

        if (!str_starts_with($manager, '\\')) {
            $className = __NAMESPACE__ . '\\'. $className;
        }

        if (!class_exists($className)) {
            return null;
        }

        $extension = new $className();
        return ($extension instanceof ExtensionInterface) ? $extension : null;
    }

    /**
     * @return BackedEnum[]
     */
    public static function allowed(string $frontend): array
    {
        $cases = [];
        $reflector = new ReflectionEnum(self::class);
        foreach ($reflector->getCases() as $case) {
            if ('noninteractive' === $frontend
                && in_array($case->getBackingValue(), [self::OUTPUT_MANAGER->value, self::PROGRESS_MANAGER->value], true)
            ) {
                continue;
            }
            $cases[] = $case->getValue();
        }
        return $cases;
    }

    public static function isAllowed(string $value, string $frontend): bool
    {
        $allowed = self::allowed($frontend);

        foreach ($allowed as $case) {
            if ($case->value === $value) {
                return true;
            }
        }

        return false;
    }
}
