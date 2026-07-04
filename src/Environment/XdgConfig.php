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

use function array_merge;
use function explode;
use function getenv;
use function is_dir;
use function mkdir;
use function str_replace;
use function sys_get_temp_dir;

use const DIRECTORY_SEPARATOR;

/**
 * Follows XDG Base Directory Specification, and retrieve all values on current platform.
 *
 * @link https://specifications.freedesktop.org/basedir/latest/
 * @author Laurent Laville
 * @since Release 9.8.0
 */
class XdgConfig extends EnvConfig implements XdgConfigInterface
{
    public function __construct()
    {
        parent::__construct('xdg');
    }

    public function getHomeDir(): string
    {
        return getenv('HOME') ?: (getenv('HOMEDRIVE') . DIRECTORY_SEPARATOR . getenv('HOMEPATH'));
    }

    public function getHomeConfigDir(): string
    {
        $homeDir = $this->getHomeDir();
        $fallback = DIRECTORY_SEPARATOR === $homeDir ? $homeDir . '.config' : $homeDir . DIRECTORY_SEPARATOR . '.config';
        return $this->get('config_home', $fallback);
    }

    public function getHomeCacheDir(): string
    {
        $fallback = $this->getHomeDir() . DIRECTORY_SEPARATOR . '.cache';
        return $this->get('cache_home', $fallback);
    }

    public function getHomeDataDir(): string
    {
        $fallback = $this->getHomeDir() . DIRECTORY_SEPARATOR . '.local' . DIRECTORY_SEPARATOR . 'share';
        return $this->get('data_home', $fallback);
    }

    public function getHomeStateDir(): string
    {
        $fallback = $this->getHomeDir() . DIRECTORY_SEPARATOR . '.local' . DIRECTORY_SEPARATOR . 'state';
        return $this->get('state_home', $fallback);
    }

    public function getConfigDirs(): array
    {
        $configDirs = $this->get('config_dirs');
        $fallback = ['/etc/xdg'];
        $configDirs = $configDirs !== null ? explode(':', $configDirs) : $fallback;
        return array_merge([$this->getHomeConfigDir()], $configDirs);
    }

    public function getDataDirs(): array
    {
        $dataDirs = $this->get('data_dirs');
        $fallback = ['/usr/local/share', '/usr/share'];
        $dataDirs = $dataDirs !== null ? explode(':', $dataDirs) : $fallback;
        return array_merge([$this->getHomeDataDir()], $dataDirs);
    }

    public function getRuntimeDir(string $fallbackDir = 'xdg-runtime-dir-fallback-'): string
    {
        if ($runtimeDir = $this->get('runtime_dir')) {
            return $runtimeDir;
        }

        $fallback = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fallbackDir . str_replace(' ', '-', $this->getUser());

        if (!is_dir($fallback)) {
            mkdir($fallback, 0700, true);
        }

        return $fallback;
    }

    public function describe(): array
    {
        return $this->__debugInfo();
    }

    public function __debugInfo(): array
    {
        return [
            new ProviderData('HOME', $this->getHomeDir(), 'Home directory'),
            new ProviderData('USER', $this->getUser(), 'User name'),
            new ProviderData('XDG_CONFIG_HOME', $this->getHomeConfigDir(), 'Home config directory'),
            new ProviderData('XDG_CACHE_HOME', $this->getHomeCacheDir(), 'Home cache directory'),
            new ProviderData('XDG_DATA_HOME', $this->getHomeDataDir(), 'Home data directory'),
            new ProviderData('XDG_STATE_HOME', $this->getHomeStateDir(), 'Home state directory'),
            new ProviderData('XDG_CONFIG_DIRS', implode(', ', $this->getConfigDirs()), 'Config directories'),
            new ProviderData('XDG_DATA_DIRS', implode(', ', $this->getDataDirs()), 'Data directories'),
            new ProviderData('XDG_RUNTIME_DIR', $this->getRuntimeDir(), 'Runtime directory'),
        ];
    }
}
