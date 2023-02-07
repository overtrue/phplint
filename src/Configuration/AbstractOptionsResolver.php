<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Configuration;

use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

use function array_key_exists;
use function ini_get;
use function sprintf;

abstract class AbstractOptionsResolver implements Resolver
{
    protected array $defaults;
    protected array $options;

    public function __construct(InputInterface $input, InputDefinition $definition, array $configuration = [])
    {
        $arguments = $input->getArguments();
        $options = $input->getOptions();

        $optionDefaults = [
            OptionDefinition::OPTION_PATH => OptionDefinition::DEFAULT_PATH,
            OptionDefinition::OPTION_CONFIG_FILE => OptionDefinition::DEFAULT_CONFIG_FILE,
            OptionDefinition::OPTION_NO_CONFIG_FILE => false,
            OptionDefinition::OPTION_EXCLUDE => OptionDefinition::DEFAULT_EXCLUDES,
            OptionDefinition::OPTION_EXTENSIONS => OptionDefinition::DEFAULT_EXTENSIONS,
            OptionDefinition::OPTION_JOBS => OptionDefinition::DEFAULT_JOBS,
            OptionDefinition::OPTION_CACHE => OptionDefinition::DEFAULT_CACHE_DIR,
            OptionDefinition::OPTION_NO_CACHE => false,
            OptionDefinition::OPTION_PROGRESS => OptionDefinition::DEFAULT_PROGRESS_WIDGET,
            OptionDefinition::OPTION_NO_PROGRESS => false,
            OptionDefinition::OPTION_JSON_FILE => null,
            OptionDefinition::OPTION_JUNIT_FILE => null,
            OptionDefinition::OPTION_WARNING => false,
            OptionDefinition::OPTION_MEMORY_LIMIT => ini_get('memory_limit'),
            OptionDefinition::OPTION_IGNORE_EXIT_CODE => false,
        ];

        $defaults = [];

        if (empty($arguments['path'])) {
            $defaults[OptionDefinition::OPTION_PATH] = $configuration[OptionDefinition::OPTION_PATH] ?? $optionDefaults[OptionDefinition::OPTION_PATH];
        } else {
            $defaults[OptionDefinition::OPTION_PATH] = $arguments['path'];
        }

        if (empty($options['exclude'])) {
            unset($options['exclude']);
        }
        if (empty($options['extensions'])) {
            unset($options['extensions']);
        }
        if (empty($options['no-cache'])) {
            unset($options['no-cache']);
        }
        if (empty($options['no-progress'])) {
            unset($options['no-progress']);
        }
        if (empty($options['warning'])) {
            unset($options['warning']);
        }

        // options that cannot be overridden by YAML config file values
        $names = [
            OptionDefinition::OPTION_CONFIG_FILE,
            OptionDefinition::OPTION_NO_CONFIG_FILE
        ];
        foreach ($names as $name) {
            $defaults[$name] = $options[$name] ?? $optionDefaults[$name];
        }

        // all options that may be overridden by YAML config file values
        $names = [
            OptionDefinition::OPTION_EXCLUDE,
            OptionDefinition::OPTION_EXTENSIONS,
            OptionDefinition::OPTION_JOBS,
            OptionDefinition::OPTION_NO_CACHE,
            OptionDefinition::OPTION_CACHE,
            OptionDefinition::OPTION_NO_PROGRESS,
            OptionDefinition::OPTION_PROGRESS,
            OptionDefinition::OPTION_JSON_FILE,
            OptionDefinition::OPTION_JUNIT_FILE,
            OptionDefinition::OPTION_WARNING,
            OptionDefinition::OPTION_MEMORY_LIMIT,
            OptionDefinition::OPTION_IGNORE_EXIT_CODE,
        ];
        foreach ($names as $name) {
            $defaults[$name] = $options[$name] ?? $configuration[$name] ?? $optionDefaults[$name];
        }

        $this->defaults = $defaults;
    }

    abstract public function factory(): Options;

    public function getOptions(): array
    {
        $options = $this->factory();
        return $this->options = $options->resolve();
    }

    public function getOption(string $name): mixed
    {
        if (!isset($this->options)) {
            $this->getOptions();
        }

        if (array_key_exists($name, $this->options)) {
            return $this->options[$name];
        }

        throw new InvalidOptionsException(sprintf('The "%s" option does not exist.', $name));
    }
}
