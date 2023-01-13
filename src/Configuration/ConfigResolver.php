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
use function ini_get;
use function is_array;
use function is_string;
use function realpath;
use function sprintf;

/**
 * @author Laurent Laville
 * @since Release 7.0.0
 */
final class ConfigResolver
{
    public const OPTION_QUIET = 'quiet';
    public const OPTION_JOBS = 'jobs';
    public const OPTION_PATH = 'path';
    public const OPTION_EXCLUDE = 'exclude';
    public const OPTION_EXTENSIONS = 'extensions';
    public const OPTION_WARNING = 'warning';
    public const OPTION_CACHE = 'cache';
    public const OPTION_NO_CACHE = 'no-cache';
    public const OPTION_CONFIG_FILE = 'configuration';
    public const OPTION_MEMORY_LIMIT = 'memory-limit';
    public const OPTION_JSON_FILE = 'json';
    public const OPTION_XML_FILE = 'xml';
    public const OPTION_NO_FILES_EXIT_CODE = 'no-files-exit-code';

    public const DEFAULT_JOBS = 5;
    public const DEFAULT_PATH = '.';
    public const DEFAULT_EXTENSIONS = ['php'];
    public const DEFAULT_CACHE_DIR = '.phplint.cache';
    public const DEFAULT_CONFIG_FILE = '.phplint.yml';
    public const DEFAULT_STANDARD_OUTPUT = 'standard output';

    private array $options = [
        self::OPTION_QUIET => false,
        self::OPTION_JOBS => self::DEFAULT_JOBS,
        self::OPTION_PATH => [self::DEFAULT_PATH],
        self::OPTION_EXCLUDE => [],
        self::OPTION_EXTENSIONS => self::DEFAULT_EXTENSIONS,
        self::OPTION_WARNING => false,
        self::OPTION_CACHE => self::DEFAULT_CACHE_DIR,
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

    /**
     * @return array<string, mixed>
     */
    public function resolve(): array
    {
        if (!empty($this->options[self::OPTION_CONFIG_FILE])) {
            $conf = $this->loadConfiguration($this->options[self::OPTION_CONFIG_FILE]);
        } else {
            $conf = [];
        }
        return $this->getOptions()->resolve($conf);
    }

    /**
     * @return Throwable[]
     */
    public function getNestedExceptions(): array
    {
        return $this->exceptions;
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

        $resolver->setDefined(array_keys($this->options));

        $resolver->setAllowedTypes(self::OPTION_QUIET, 'bool');
        $resolver->setAllowedTypes(self::OPTION_JOBS, ['int', 'string']);
        $resolver->setAllowedTypes(self::OPTION_PATH, ['string', 'string[]']);
        $resolver->setAllowedTypes(self::OPTION_EXCLUDE, ['string[]']);
        $resolver->setAllowedTypes(self::OPTION_EXTENSIONS, ['string[]']);
        $resolver->setAllowedTypes(self::OPTION_WARNING, 'bool');
        $resolver->setAllowedTypes(self::OPTION_CACHE, ['null', 'string']);
        $resolver->setAllowedTypes(self::OPTION_NO_CACHE, 'bool');
        $resolver->setAllowedTypes(self::OPTION_CONFIG_FILE, 'string');
        $resolver->setAllowedTypes(self::OPTION_MEMORY_LIMIT, ['int', 'string']);
        $resolver->setAllowedTypes(self::OPTION_JSON_FILE, ['null', 'string']);
        $resolver->setAllowedTypes(self::OPTION_XML_FILE, ['null', 'string']);
        $resolver->setAllowedTypes(self::OPTION_NO_FILES_EXIT_CODE, 'bool');

        $resolver->setNormalizer(self::OPTION_JOBS, function (Options $options, $value) {
            if (is_string($value)) {
                $value = (int) $value;
            }
            return $value;
        });
        $resolver->setNormalizer(self::OPTION_PATH, function (Options $options, $value) {
            if (is_string($value)) {
                $value = [$value];
            }
            return $value;
        });
        $resolver->setNormalizer(self::OPTION_CONFIG_FILE, function (Options $options, $value) {
            $canonical =  $value ? realpath($value) : false;
            if ($canonical === false) {
                return '';
            }
            return $canonical;
        });
        $resolver->setNormalizer(self::OPTION_JSON_FILE, function (Options $options, $value) {
            if (null === $value) {
                $value = 'php://stdout';
            } elseif (self::DEFAULT_STANDARD_OUTPUT === $value) {
                $value = false;
            }
            return $value;
        });
        $resolver->setNormalizer(self::OPTION_XML_FILE, function (Options $options, $value) {
            if (null === $value) {
                $value = 'php://stdout';
            } elseif (self::DEFAULT_STANDARD_OUTPUT === $value) {
                $value = false;
            }
            return $value;
        });

        return $resolver;
    }
}
