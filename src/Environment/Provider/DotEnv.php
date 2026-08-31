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
use Overtrue\PHPLint\Environment\EnvConfigInterface;
use Overtrue\PHPLint\Environment\ProviderData;
use Overtrue\PHPLint\Environment\ProviderInterface;
use Overtrue\PHPLint\Environment\XdgConfig;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

use function array_push;
use function array_unique;
use function array_unshift;
use function dirname;
use function explode;
use function file_exists;
use function getcwd;
use function implode;
use function in_array;
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

    protected EnvConfigInterface $envConfig;
    protected string $envPrefix;
    protected string $envKey;
    protected string $debugKey;
    protected bool $disableDotEnvLookup;
    protected bool $overrideExistingVars;
    protected string $dotEnvPath;
    protected string $projectDirectory;

    private array $defaultFallback = [];

    public function __construct(array $options = [])
    {
        $this->envPrefix = $options['env_prefix'] ?? 'PLINT';

        $this->envKey = $options['env_var_name'] ??= $this->envPrefix .'_ENV';
        $this->debugKey = $options['debug_var_name'] ??= $this->envPrefix . '_DEBUG';

        $this->disableDotEnvLookup = $options['disable_dotenv'] ?? false;
        $this->overrideExistingVars = $options['dotenv_overload'] ?? false;
        $this->dotEnvPath = $options['dotenv_path'] ?? '.env';
        $this->projectDirectory = $options['project_dir'] ?? getcwd();

        $this->envConfig = new EnvConfig($this->envPrefix);

        $this->defaultFallback = $this->envConfig->getDefaultFallback($this->envConfig->get('env', 'dev'));
        $this->defaultFallback['project_dir'] = $this->projectDirectory;
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
                    $dotenv = new \Symfony\Component\Dotenv\Dotenv($this->envKey, $this->debugKey);
                    $dotenv->usePutenv();
                    $dotenv->loadEnv($envFile, overrideExistingVars: $this->overrideExistingVars);
                    $data[] = $this->providerData('dotEnvPath', $envFile, 'The path to the dotenv file');
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

        array_push($data, ...$xdg->describe());

        $variables = [
            'env' => 'The name of the environment PHPLint runs it',
            'debug' => 'Toggles the debug mode',
            'frontend' => 'The name of the interface PHPLint runs it',
            'project_dir' => 'The project directory relative to the parent directory of your composer.json file',
            'log' => 'Identify what class to use as PSR-3 compatible logger',
            'diagnostic' => 'Identify what diagnostics are runs',
            'mode' => 'This setting controls which PHPLint features are enabled',
            'allow_plugins' => 'List of extensions allowed to be executed',
            'default_plugins' => 'List of extensions loaded for the current command',
        ];

        foreach ($variables as $key => $desc) {
            $value = $this->envConfig->get($key, $this->defaultFallback[$key] ?? null);

            if (in_array($key, ['allow_plugins', 'default_plugins'], true)) {
                $value = explode(',', $value);
            }

            if (null !== $value) {
                $data[] = $this->providerData(strtoupper(sprintf('%s_%s', $this->envPrefix, $key)), $value, $desc);
            }
        }

        return $data;
    }

    protected function providerData(string $setting, mixed $value, ?string $description = null): ProviderData
    {
        return new ProviderData($setting, json_encode($value, JSON_UNESCAPED_SLASHES), $description);
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
