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

namespace Overtrue\PHPLint\Configuration;

use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

use function array_key_exists;
use function in_array;
use function ini_get;
use function realpath;
use function sprintf;

/**
 * @author Laurent Laville
 * @since Release 9.0.0
 */
abstract class AbstractOptionsResolver implements Resolver
{
    protected array $defaults;
    protected array $options;

    public function __construct(
        protected InputInterface $input,  // @deprecated since Release 9.8.0, will be removed in next API version
        array $configuration = []
    ) {
        $options = $configuration;

        $optionDefaults = [
            OptionDefinition::PATH => realpath(OptionDefinition::DEFAULT_PATH),
            OptionDefinition::CONFIGURATION => realpath(OptionDefinition::DEFAULT_CONFIG_FILE),
            OptionDefinition::NO_CONFIGURATION => false,
            OptionDefinition::EXCLUDE => OptionDefinition::DEFAULT_EXCLUDES,
            OptionDefinition::FILE_EXTENSIONS => OptionDefinition::DEFAULT_EXTENSIONS,
            OptionDefinition::JOBS => OptionDefinition::DEFAULT_JOBS,
            OptionDefinition::CACHE => OptionDefinition::DEFAULT_CACHE_DIR,
            OptionDefinition::CACHE_DIR => OptionDefinition::DEFAULT_CACHE_DIR,
            OptionDefinition::NO_CACHE => false,
            OptionDefinition::CACHE_TTL => OptionDefinition::DEFAULT_CACHE_TTL,
            OptionDefinition::CACHE_ADAPTER => OptionDefinition::DEFAULT_CACHE_ADAPTER,
            OptionDefinition::PROGRESS => OptionDefinition::DEFAULT_PROGRESS_WIDGET,
            OptionDefinition::NO_PROGRESS => false,
            OptionDefinition::OUTPUT_FILE => null,
            OptionDefinition::OUTPUT_FORMAT => OptionDefinition::DEFAULT_FORMATS,
            OptionDefinition::WARNING => false,
            OptionDefinition::OPTION_MEMORY_LIMIT => ini_get('memory_limit'),
            OptionDefinition::IGNORE_EXIT_CODE => false,
            OptionDefinition::BOOTSTRAP => OptionDefinition::DEFAULT_BOOTSTRAP,
            OptionDefinition::DRY_RUN => false,
        ];

        $defaults = [];

        // options that cannot be overridden by YAML config file values
        $name = OptionDefinition::CONFIGURATION;
        $withoutConfigFile = in_array($options[$name], ['', 'never']);
        $defaults[$name] = $withoutConfigFile ? '' : $options[$name];

        $name = OptionDefinition::NO_CONFIGURATION;
        $defaults[$name] = $withoutConfigFile;

        // all options that may be overridden by YAML config file values
        $names = [
            OptionDefinition::PATH,
            OptionDefinition::EXCLUDE,
            OptionDefinition::FILE_EXTENSIONS,
            OptionDefinition::JOBS,
            OptionDefinition::NO_CACHE,
            OptionDefinition::CACHE,
            OptionDefinition::CACHE_DIR,
            OptionDefinition::CACHE_TTL,
            OptionDefinition::CACHE_ADAPTER,
            OptionDefinition::NO_PROGRESS,
            OptionDefinition::PROGRESS,
            OptionDefinition::OUTPUT_FILE,
            OptionDefinition::OUTPUT_FORMAT,
            OptionDefinition::WARNING,
            OptionDefinition::OPTION_MEMORY_LIMIT,
            OptionDefinition::IGNORE_EXIT_CODE,
            OptionDefinition::BOOTSTRAP,
            OptionDefinition::DRY_RUN,
        ];
        foreach ($names as $name) {
            $defaults[$name] = $options[$name] ?? $optionDefaults[$name];
        }

        $this->defaults = $defaults;
    }

    abstract public function factory(): Options;

    public function getOptions(): array
    {
        $optionsFactory = $this->factory();
        return $this->options = $optionsFactory->resolve();
    }

    public function getOption(string $name): mixed
    {
        if (!isset($this->options)) {
            try {
                $this->getOptions();
            } catch (Exception) {
                return null;
            }
        }

        if (array_key_exists($name, $this->options)) {
            return $this->options[$name];
        }

        throw new InvalidOptionsException(sprintf('The "%s" option does not exist.', $name));
    }
}
