<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Configuration;

use Symfony\Component\OptionsResolver\Options as SymfonyOptions;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function array_keys;

class OptionsFactory implements Options
{
    private array $defaults;

    public function __construct(array $defaults)
    {
        $this->defaults = $defaults;
    }

    public function resolve(): array
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $resolver->setDefaults($this->defaults);
        return $resolver->resolve();
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $definitions = [
            OptionDefinition::OPTION_JOBS => ['int', 'string'],
            OptionDefinition::OPTION_EXCLUDE => ['string[]'],
            OptionDefinition::OPTION_EXTENSIONS => ['string[]'],
            OptionDefinition::OPTION_WARNING => 'bool',
            OptionDefinition::OPTION_CACHE => ['null', 'string'],
            OptionDefinition::OPTION_NO_CACHE => 'bool',
            OptionDefinition::OPTION_CONFIG_FILE => 'string',
            OptionDefinition::OPTION_MEMORY_LIMIT => ['int', 'string'],
            OptionDefinition::OPTION_JSON_FILE => ['bool', 'null', 'string'],
            OptionDefinition::OPTION_JUNIT_FILE => ['bool', 'null', 'string'],
            OptionDefinition::OPTION_IGNORE_EXIT_CODE => 'bool',
            'progress' => ['null', 'string'],
            'path' => ['null', 'string', 'string[]'],

            'ansi' => ['null', 'bool'],
            'help' => ['null', 'bool'],
            'no-configuration' => 'bool',
            'no-interaction' => 'bool',
            'no-progress' => 'bool',
            'quiet' => ['null', 'bool'],
            'verbose' => ['null', 'bool'],
            'version' => ['null', 'bool'],

            'command' => ['null', 'string'],
        ];

        $resolver->setDefined(array_keys($definitions));

        foreach ($definitions as $option => $allowedTypes) {
            $resolver->setAllowedTypes($option, $allowedTypes);
        }

        $resolver->setNormalizer(OptionDefinition::OPTION_PATH, function (SymfonyOptions $options, $value) {
            return (array) $value;
        });

        $resolver->setNormalizer(OptionDefinition::OPTION_JOBS, function (SymfonyOptions $options, $value) {
            return (int) $value;
        });

        $outputFormat = function (SymfonyOptions $options, $value) {
            if (true === $value) {
                $value = 'php://stdout';
            }
            return $value;
        };
        $resolver->setNormalizer(OptionDefinition::OPTION_JSON_FILE, $outputFormat);
        $resolver->setNormalizer(OptionDefinition::OPTION_JUNIT_FILE, $outputFormat);
    }
}
