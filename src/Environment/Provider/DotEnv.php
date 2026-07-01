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

namespace Overtrue\PHPLint\Environment\Provider;

use Composer\InstalledVersions;
use Overtrue\PHPLint\Environment\EnvConfig;
use Overtrue\PHPLint\Environment\ProviderData;
use Overtrue\PHPLint\Environment\ProviderInterface;
use Overtrue\PHPLint\Environment\XdgConfig;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Dotenv\Dotenv as SymfonyDotenv;

use function array_unique;
use function array_unshift;
use function dirname;
use function file_exists;
use function getcwd;
use function implode;
use function json_encode;
use function realpath;
use function sprintf;
use function str_starts_with;
use function strtoupper;

use const JSON_UNESCAPED_SLASHES;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
class DotEnv implements ProviderInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected string $envKey;
    protected string $debugKey;
    protected bool $disableDotEnvLookup;
    protected bool $overrideExistingVars;
    protected string $dotEnvPath;
    protected string $projectDirectory;

    public function __construct(array $options = [])
    {
        $this->envKey = $options['env_var_name'] ??= 'PHPLINT_ENV';
        $this->debugKey = $options['debug_var_name'] ??= 'PHPLINT_DEBUG';

        $this->disableDotEnvLookup = $options['disable_dotenv'] ?? false;
        $this->overrideExistingVars = $options['dotenv_overload'] ?? false;
        $this->dotEnvPath = $options['dotenv_path'] ?? '.env';
        $this->projectDirectory = $options['project_dir'] ?? getcwd();
    }

    public function describe(): ?array
    {
        $data = [];

        $xdg = new XdgConfig();

        if (!$this->disableDotEnvLookup) {
            // @link https://github.com/symfony/dotenv
            $packageName = 'symfony/dotenv';

            // @see https://getcomposer.org/doc/07-runtime.md#installed-versions
            if (!InstalledVersions::isInstalled($packageName)) {
                $this->logger->warning(
                    'Package "{packageName}" is not installed.',
                    ['packageName' => $packageName]
                );
            } else {
                $dirs = $xdg->getConfigDirs();
                $envFile = $this->lookupDotEnvFile($this->dotEnvPath, $dirs);

                if (null !== $envFile) {
                    $dotenv = new SymfonyDotenv($this->envKey, $this->debugKey);
                    $dotenv->usePutenv();
                    $dotenv->loadEnv($envFile, overrideExistingVars: $this->overrideExistingVars);
                    $data[] = $this->providerData('dotEnvPath', $envFile);
                } else {
                    $path = dirname($this->dotEnvPath);
                    if ($path === '.') {
                        $path = realpath('.');
                    }
                    array_unshift($dirs, $path, $this->projectDirectory);
                    $this->logger->warning(
                        'Cannot find any dot env file in any of the following directory: {dirs}',
                        ['dirs' => implode(', ', array_unique($dirs))]
                    );
                }
            }
        }

        foreach ($xdg->describe() as $setting => $value) {
            $data[] = $this->providerData($setting, $value);
        }

        $prefix = 'phplint';
        $env = new EnvConfig($prefix);
        foreach (['env', 'debug', 'project_dir', 'diagnostic', 'log', 'frontend'] as $key) {
            $defaultFallback = ($key === 'project_dir') ? $this->projectDirectory : null;
            $value = $env->get($key, $defaultFallback);
            if (null !== $value) {
                $data[] = $this->providerData(strtoupper(sprintf('%s_%s', $prefix, $key)), $value);
            }
        }

        return $data;
    }

    protected function providerData(string $setting, mixed $value): ProviderData
    {
        return new ProviderData($setting, json_encode($value, JSON_UNESCAPED_SLASHES));
    }

    /**
     * If $envFile provided is absolute path, then try only with this only one, and don't search elsewhere.
     *
     * Otherwise, searches for ".env" files in XDG_CONFIG_DIRS, and returns the first one that matched.
     *
     * Fallback search into project directory, if none found in previous strategy.
     */
    protected function lookupDotEnvFile(string $envFile, array $configDirs): ?string
    {
        if (str_starts_with('/', $envFile)) {
            return file_exists($envFile) ? $envFile : null;
        }

        foreach ($configDirs as $configDir) {
            $filePath = $configDir . DIRECTORY_SEPARATOR . $envFile;
            if (file_exists($filePath)) {
                return $filePath;
            }
        }

        $filePath =  $this->projectDirectory . DIRECTORY_SEPARATOR . $envFile;
        return file_exists($filePath) ? $filePath : null;
    }
}
