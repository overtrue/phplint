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

use Closure;
use Symfony\Component\OptionsResolver\Options as SymfonyOptions;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function array_keys;
use function filter_var;
use function in_array;
use function is_bool;

use const FILTER_VALIDATE_BOOLEAN;

/**
 * @author Laurent Laville
 * @since Release 9.0.0
 */
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
            OptionDefinition::PATH => ['null', 'string', 'string[]'],
            OptionDefinition::EXCLUDE => ['string[]'],
            OptionDefinition::EXTENSIONS => ['string[]'],
            OptionDefinition::JOBS => ['int', 'string'],
            OptionDefinition::CONFIGURATION => 'string',
            OptionDefinition::NO_CONFIGURATION => 'bool',
            OptionDefinition::CACHE => ['null', 'string'],
            OptionDefinition::NO_CACHE => 'bool',
            OptionDefinition::PROGRESS => ['null', 'string'],
            OptionDefinition::NO_PROGRESS => 'bool',
            OptionDefinition::LOG_JSON => ['bool', 'string'],
            OptionDefinition::LOG_JUNIT => ['bool', 'string'],
            OptionDefinition::WARNING => 'bool',
            OptionDefinition::OPTION_MEMORY_LIMIT => ['int', 'string'],
            OptionDefinition::IGNORE_EXIT_CODE => 'bool',

            'ansi' => ['null', 'bool'],
            'help' => ['null', 'bool'],
            'no-interaction' => 'bool',
            'quiet' => ['null', 'bool'],
            'verbose' => ['null', 'bool'],
            'version' => ['null', 'bool'],
            'command' => ['null', 'string'],
        ];

        $resolver->setDefined(array_keys($definitions));

        foreach ($definitions as $option => $allowedTypes) {
            $resolver->setAllowedTypes($option, $allowedTypes);
        }

        $resolver->setNormalizer(OptionDefinition::PATH, function (SymfonyOptions $options, $value) {
            return (array) $value;
        });

        $resolver->setNormalizer(OptionDefinition::JOBS, function (SymfonyOptions $options, $value) {
            return (int) $value;
        });

        $names = [
            OptionDefinition::LOG_JSON,
            OptionDefinition::LOG_JUNIT,
        ];
        foreach ($names as $name) {
            $resolver->setNormalizer($name, Closure::fromCallable([$this, 'logNormalizer']));
        }
    }

    /**
     * Reused by unit tests suite ConsoleConfigTest
     */
    public static function logNormalizer(SymfonyOptions $options, $value)
    {
        $bool = static::toBool($value);
        if (is_bool($bool)) {
            $value = $bool ? OptionDefinition::DEFAULT_STANDARD_OUTPUT : false;
        }
        return $value;
    }

    /**
     * Best strategy to convert string to boolean
     *
     *
     * @link https://stackoverflow.com/questions/7336861/how-to-convert-string-to-boolean-php#answer-15075609
     * @link https://www.php.net/manual/en/function.filter-var
     */
    public static function toBool(mixed $value): bool|int
    {
        $booleanValueDomain = [
            false, 'false', 0, '0', 'off', 'no',
            true, 'true', 1, '1', 'on', 'yes',
        ];

        if (in_array($value, ['', null], true)) {
            // CAUTION: null or empty string must be considered as logging to standard output (like true boolean value)
            $out = true;
        } elseif (in_array($value, $booleanValueDomain, true)) {
            $out = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        } else {
            $out = -1;
        }
        return $out;
    }
}
