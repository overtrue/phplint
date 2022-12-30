<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Configuration;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

use Throwable;

use function array_keys;
use function array_replace_recursive;
use function count;
use function dirname;
use function getcwd;
use function ini_get;
use function is_array;
use function is_dir;
use function is_string;
use function realpath;
use function reset;
use function rtrim;
use function sprintf;

use const DIRECTORY_SEPARATOR;

/**
 * @author Laurent Laville
 */
final class ConfigResolver
{
    public const OPTION_QUIET = 'quiet';
    public const OPTION_JOBS = 'jobs';
    public const OPTION_PATH = 'path';
    public const OPTION_EXCLUDE = 'exclude';
    public const OPTION_EXTENSIONS = 'extensions';
    public const OPTION_WARNING = 'warning';
    public const OPTION_CACHE_FILE = 'cache';
    public const OPTION_NO_CACHE = 'no-cache';
    public const OPTION_CONFIG_FILE = 'configuration';
    public const OPTION_MEMORY_LIMIT = 'memory-limit';
    public const OPTION_JSON_FILE = 'json';
    public const OPTION_XML_FILE = 'xml';
    public const OPTION_NO_FILES_EXIT_CODE = 'no-files-exit-code';

    public const DEFAULT_JOBS = 5;
    public const DEFAULT_PATH = '.';
    public const DEFAULT_EXTENSIONS = ['php'];
    public const DEFAULT_CACHE_FILE = '.phplint-cache';
    public const DEFAULT_CONFIG_FILE = '.phplint.yml';

    private array $options = [
        self::OPTION_QUIET => false,
        self::OPTION_JOBS => self::DEFAULT_JOBS,
        self::OPTION_PATH => self::DEFAULT_PATH,
        self::OPTION_EXCLUDE => [],
        self::OPTION_EXTENSIONS => self::DEFAULT_EXTENSIONS,
        self::OPTION_WARNING => false,
        self::OPTION_CACHE_FILE => self::DEFAULT_CACHE_FILE,
        self::OPTION_NO_CACHE => false,
        self::OPTION_CONFIG_FILE => self::DEFAULT_CONFIG_FILE,
        self::OPTION_MEMORY_LIMIT => false,
        self::OPTION_JSON_FILE => false,
        self::OPTION_XML_FILE => false,
        self::OPTION_NO_FILES_EXIT_CODE => false,
    ];

    /**
     * @var Throwable[]
     */
    private array $exceptions = [];

    public function __construct(InputInterface $input)
    {
        foreach (array_keys($this->options) as $option) {
            if (self::OPTION_PATH == $option) {
                $this->options[$option] = $input->getArgument('path');
            } elseif (self::OPTION_MEMORY_LIMIT == $option) {
                $this->options[$option] = ini_get('memory_limit');
            } else {
                $this->options[$option] = $input->getOption($option);
            }
        }

        if ($input->getOption('no-configuration')) {
            $this->options[self::OPTION_CONFIG_FILE] = '';
        }
    }

    public function resolve(): array
    {
        if (!empty($this->options[self::OPTION_CONFIG_FILE])) {
            $conf = $this->loadConfiguration($this->options[self::OPTION_CONFIG_FILE]);
            $conf = array_replace_recursive($conf, $this->options);
            $config = $this->getOptions()->resolve($conf);
        } else {
            $config = $this->options;
        }
        return $config;
    }

    /**
     * @return Throwable[]
     */
    public function getNestedExceptions(): array
    {
        return $this->exceptions;
    }

    /**
     * @param string[] $inputPath
     */
    private function getConfigFile(array $inputPath): false|string
    {
        if (1 == count($inputPath) && $first = reset($inputPath)) {
            $dir = is_dir($first) ? $first : dirname($first);
        } else {
            $dir = getcwd() . DIRECTORY_SEPARATOR;
        }

        $filename = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . self::DEFAULT_CONFIG_FILE;

        return realpath($filename);
    }

    private function loadConfiguration(string $path): array
    {
        try {
            $configuration = Yaml::parseFile($path);
            if (is_array($configuration)) {
                if (!is_array($configuration[self::OPTION_PATH])) {
                    $configuration[self::OPTION_PATH] = [$configuration[self::OPTION_PATH]];
                }
                return $configuration;
            }
            $this->exceptions[] = new ParseException(
                sprintf('Invalid content type in "%s". Expected yaml format.', $path),
                1
            );
        } catch (ParseException $e) {
            $this->exceptions[] = $e;
        }
        return [];
    }

    private function getOptions(): OptionsResolver
    {
        $resolver = new OptionsResolver();

        $resolver->setDefaults($this->options);

        $resolver->setRequired(self::OPTION_PATH);

        $resolver->setAllowedTypes(self::OPTION_QUIET, 'bool');
        $resolver->setAllowedTypes(self::OPTION_JOBS, 'int');
        $resolver->setAllowedTypes(self::OPTION_PATH, ['string', 'string[]']);
        $resolver->setAllowedTypes(self::OPTION_EXCLUDE, ['string[]']);
        $resolver->setAllowedTypes(self::OPTION_EXTENSIONS, ['string[]']);
        $resolver->setAllowedTypes(self::OPTION_WARNING, 'bool');
        $resolver->setAllowedTypes(self::OPTION_CACHE_FILE, ['null', 'string']);
        $resolver->setAllowedTypes(self::OPTION_NO_CACHE, 'bool');
        $resolver->setAllowedTypes(self::OPTION_CONFIG_FILE, 'string');
        $resolver->setAllowedTypes(self::OPTION_MEMORY_LIMIT, ['int', 'string']);
        $resolver->setAllowedTypes(self::OPTION_JSON_FILE, ['null', 'string']);
        $resolver->setAllowedTypes(self::OPTION_XML_FILE, ['null', 'string']);

        $resolver->setNormalizer(self::OPTION_PATH, function (Options $options, $value) {
            if (is_string($value)) {
                $value = [$value];
            }
            return $value;
        });
        $resolver->setNormalizer(self::OPTION_CONFIG_FILE, function (Options $options, $value) {
            $configFile = $this->getConfigFile($this->options[self::OPTION_PATH]);
            if ($configFile === false) {
                return '';
            }
            return $configFile;
        });

        return $resolver;
    }
}
