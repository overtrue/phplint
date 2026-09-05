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

namespace Overtrue\PHPLint\Environment;

use Overtrue\PHPLint\Console\ConsoleLogger;
use Overtrue\PHPLint\Extension\ExtensionEnum;
use function getenv;
use function implode;
use function rtrim;
use function sort;
use function str_replace;
use function strtoupper;
use function strtr;
use const PHP_SAPI;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
class EnvConfig implements EnvConfigInterface
{
    protected string $prefix;

    /**
     * Class constructor.
     *
     * @param string $prefix
     *   The string to appear before every environment variable key.
     *   For example, if the prefix is 'MYAPP_', then the key 'foo_bar' will be
     *   fetched from the environment variable MYAPP_FOO_BAR.
     */
    public function __construct(string $prefix = 'PLINT')
    {
        // Ensure that the prefix is always uppercase, and always
        // ends with a '_', regardless of the form the caller provided.
        $this->prefix = strtoupper(rtrim($prefix, '_')) . '_';
    }

    public function getUser(): string
    {
        return getenv('USER') ?: getenv('MY_USER') ?: getenv('USERNAME') ?: '';
    }

    public function get(string $key, mixed $defaultFallback = null): mixed
    {
        $envKey = $this->prefix . strtoupper(strtr($key, '.-', '__'));
        $envKey = str_replace($this->prefix . $this->prefix, $this->prefix, $envKey);
        return getenv($envKey) ?: ($defaultFallback[$key] ?? $defaultFallback);
    }

    public function getDefaultFallback(string $envName): array
    {
        $allowPlugins = [
            ExtensionEnum::CACHE_MANAGER->value,
        ];

        $defaultPlugins = [
            ExtensionEnum::CACHE_MANAGER->value,
        ];

        $defaultFrontend = PHP_SAPI;

        if ('dev' === $envName) {
            $allowPlugins[] = ExtensionEnum::DIAGNOSE_MANAGER->value;
            $allowPlugins[] = ExtensionEnum::PROFILE_MANAGER->value;
            $allowPlugins[] = ExtensionEnum::PROGRESS_MANAGER->value;

            $defaultPlugins[] = ExtensionEnum::DIAGNOSE_MANAGER->value;
            $defaultPlugins[] = ExtensionEnum::PROFILE_MANAGER->value;
            $defaultPlugins[] = ExtensionEnum::PROGRESS_MANAGER->value;

            if ('cli' === $this->get('frontend', $defaultFrontend)) {
                $allowPlugins[] = ExtensionEnum::OUTPUT_MANAGER->value;
                $defaultPlugins[] = ExtensionEnum::OUTPUT_MANAGER->value;
            }
        }
        sort($allowPlugins);
        sort($defaultPlugins);

        return [
            'allow_plugins' => implode(',', $allowPlugins),
            'default_plugins' => implode(',', $defaultPlugins),
            'mode' => 'off',
            'frontend' => $defaultFrontend,
            'env' => 'dev',
            'debug' => false,
            'logger' => ConsoleLogger::class,
        ];
    }
}
