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

use function getenv;
use function rtrim;
use function str_replace;
use function strtoupper;
use function strtr;

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
    public function __construct(string $prefix)
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
        return getenv($envKey) ?: $defaultFallback;
    }
}
