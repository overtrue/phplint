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

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
interface XdgConfigInterface extends EnvConfigInterface
{
    public function getHomeDir(): string;

    public function getHomeConfigDir(): string;

    public function getHomeCacheDir(): string;

    public function getHomeDataDir(): string;

    public function getHomeStateDir(): string;

    public function getConfigDirs(): array;

    public function getDataDirs(): array;

    public function getRuntimeDir(string $fallbackDir = 'xdg-runtime-dir-fallback-'): string;
}
