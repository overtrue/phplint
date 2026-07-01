<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Configuration\Resolver;

use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Environment\Provider\DotEnv;
use Overtrue\PHPLint\Environment\XdgConfig;
use Overtrue\PHPLint\Environment\XdgConfigInterface;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Attribute\Reflection\ReflectionMember;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Dotenv\Dotenv as SymfonyDotenv;

use function array_merge;
use function file_exists;
use function getcwd;
use function is_readable;
use function realpath;

use const DIRECTORY_SEPARATOR;

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

        $value = $input->hasOption($argumentName) ? $input->getOption($argumentName) : ($this->defaultValues[$argumentName] ?? 'auto');

        if ($input->hasParameterOption('--no-' . $argumentName, true)) {
            // to keep BC with previous versions 9.7.x
            $value = 'never';
        }

        $configFile = match ($value) {
            'auto' => $this->autoDiscovery(),
            'always' => $this->alwaysDiscovery($argumentName),
            'never' => $this->neverDiscovery(),
            default => $this->defaultDiscovery($value),
        };
        $resolved = [$configFile];

        #\var_dump([$argumentName, $member->getName(), $argumentType, $argumentAttributes, $resolved]);
        /*
        \var_dump(['XDG env vars' => \array_filter($_SERVER, function ($key) {
            return \str_starts_with($key, 'XDG_');
        }, \ARRAY_FILTER_USE_KEY )]);
        */
        return $resolved;
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
        if (!\class_exists('\Symfony\Component\Dotenv\Dotenv')) {
            return '';
        }
        return $this->scanFile();
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
        return (!file_exists($filename) || !is_readable($filename)) ? '' : (realpath($filename) ? : '');
    }

    private function scanFile(string $envPrefix = 'PHPLINT'): string
    {
        $xdg = $this->xdgConfig;

        $values = [
            'XDG_CONFIG_HOME' => $xdg->getHomeConfigDir(),
            'XDG_CONFIG_DIRS' => $xdg->getConfigDirs(),
            'XDG_CACHE_HOME' => $xdg->getHomeCacheDir(),
            'XDG_DATA_HOME' => $xdg->getHomeDataDir(),
            'XDG_DATA_DIRS' => $xdg->getDataDirs(),
        ];

        $dotenv = new Dotenv($envPrefix . '_ENV', $envPrefix . '_DEBUG');
        $dotenv->populate($values);

        $dirs = array_merge([getcwd()], $xdg->getConfigDirs());

        foreach ($dirs as $dir) {
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
