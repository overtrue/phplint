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

use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Environment\XdgConfig;
use Overtrue\PHPLint\Environment\XdgConfigInterface;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Attribute\Reflection\ReflectionMember;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Dotenv\Dotenv as SymfonyDotenv;

use function class_exists;
use function file_exists;
use function getcwd;
use function is_readable;

use const DIRECTORY_SEPARATOR;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
class ConfigValueResolver implements ValueResolverInterface
{
    public function __construct(
        protected array $optionNamesAllowed = [OptionDefinition::CONFIGURATION],
        protected array $defaultValues = [OptionDefinition::CONFIGURATION => OptionDefinition::DEFAULT_CONFIG_FILE],
        protected ?array $configFileCandidates = null,
        private ?XdgConfigInterface $xdgConfig = null,
    ) {
        $this->configFileCandidates ??= static::getDefaultConfigFileCandidates();
        $this->xdgConfig ??= new XdgConfig();
    }

    public function resolve(string $argumentName, InputInterface $input, ReflectionMember $member): iterable
    {
        $argumentType = $member->getType()?->getName();

        if ($argumentType !== 'string') {
            return [];
        }

        $argumentAttributes = $member->getAttribute(Option::class);
        // retrieve the argument name defined by the #[Option(name:)] attribute, or fallback to PHP variable name
        $argumentName = $argumentAttributes?->name ? : $argumentName;

        if (!in_array($argumentName, $this->optionNamesAllowed, true)) {
            return [];
        }

        if ($input->hasOption('no-' . $argumentName) && $input->getOption('no-' . $argumentName)) {
            // to keep BC with previous versions 9.7.x
            $value = 'never';
        } else {
            $value = $input->hasOption($argumentName) ? $input->getOption($argumentName) : ($this->defaultValues[$argumentName] ?? 'auto');
        }

        $configFile = match ($value) {
            'auto' => $this->autoDiscovery(),
            'always' => $this->alwaysDiscovery($argumentName),
            'never' => $this->neverDiscovery(),
            default => $this->defaultDiscovery($value),
        };

        return [$configFile];
    }

    public static function getDefaultConfigFileCandidates(): array
    {
        $basename = '.phplint';
        $fileExt = ['php', 'yaml', 'yml', 'json'];

        $defaults = [];

        foreach ($fileExt as $ext) {
            $defaults[] = $basename . '.' . $ext;
        }

        foreach ($fileExt as $ext) {
            $defaults[] = $basename . '.dist.' . $ext;
        }

        return $defaults;
    }

    private function autoDiscovery(): string
    {
        if (class_exists(SymfonyDotenv::class)) {
            $xdg = $this->xdgConfig;

            $values = [
                'XDG_CONFIG_HOME' => $xdg->getHomeConfigDir(),
                'XDG_CONFIG_DIRS' => $xdg->getConfigDirs(),
                'XDG_CACHE_HOME' => $xdg->getHomeCacheDir(),
                'XDG_DATA_HOME' => $xdg->getHomeDataDir(),
                'XDG_DATA_DIRS' => $xdg->getDataDirs(),
            ];

            (new SymfonyDotenv())->populate($values);
        }

        $filename = $this->scanFile($this->xdgConfig->getConfigDirs());

        if (empty($filename)) {
            // last chance (to be compatible with API 9.7.x), try to search into current working directory
            $filename = $this->scanFile([getcwd()]);
        }

        return $filename;
    }

    private function alwaysDiscovery(string $argumentName): string
    {
        return $this->defaultDiscovery($this->defaultValues[$argumentName] ?? '');
    }

    private function neverDiscovery(): string
    {
        return '';
    }

    private function defaultDiscovery(string $filename): string
    {
        return (!file_exists($filename) || !is_readable($filename)) ? '' : ($filename ? : '');
    }

    private function scanFile(array $directories): string
    {
        foreach ($directories as $dir) {
            foreach ($this->configFileCandidates as $fileCandidate) {
                $filename = $dir . DIRECTORY_SEPARATOR . $fileCandidate;
                if (file_exists($filename)) {
                    return $filename;
                }
            }
        }

        return '';
    }
}
