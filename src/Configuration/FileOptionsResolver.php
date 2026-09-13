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

use JsonException;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

use function array_filter;
use function array_replace;
use function file_get_contents;
use function is_array;
use function is_file;
use function is_readable;
use function json_decode;
use function pathinfo;
use function sprintf;

use const JSON_THROW_ON_ERROR;
use const PATHINFO_EXTENSION;

/**
 * @author Laurent Laville
 * @since Release 9.0.0
 */
final class FileOptionsResolver extends AbstractOptionsResolver
{
    public function __construct(
        protected InputInterface $input,
        array $configuration = []
    ) {
        $withoutConfigFile = $configuration[OptionDefinition::NO_CONFIGURATION] ?? false;

        $configFile = $withoutConfigFile
            ? ''
            : ($configuration[OptionDefinition::CONFIGURATION] ?? null)
        ;
        if (null === $configFile) {
            if (true === $input->hasOption(OptionDefinition::CONFIGURATION)) {
                $configFile = $input->getOption(OptionDefinition::CONFIGURATION);
            } else {
                $configFile = '';
            }
        }

        if (!empty($configFile)) {
            $fileConf = $this->parseFile($configFile);
            $configuration = array_replace(array_filter($fileConf), array_filter($configuration));
            $configuration[OptionDefinition::CONFIGURATION] = $configFile;
        }
        parent::__construct($input, $configuration);
    }

    public function factory(): Options
    {
        return new OptionsFactory($this->defaults);
    }


    private function parseFile(string $filename): array
    {
        $configuration = match (pathinfo($filename, PATHINFO_EXTENSION)) {
            'yml', 'yaml' => $this->parseYamlConfiguration($filename),
            'json' => $this->parseJsonConfiguration($filename),
            'php' => $this->parsePhpConfiguration($filename),
            default => throw new InvalidOptionsException(sprintf('File format "%s" is not accepted.', $filename))
        };

        // Checks that config file contents adhere to configuration syntax
        $factory = new OptionsFactory([]);
        return $factory->resolve($configuration);
    }

    private function parseYamlConfiguration(string $filename): array
    {
        try {
            $configuration = Yaml::parseFile($filename);
        } catch (ParseException $e) {
            // If the file could not be read or the YAML is not valid
            $configuration = [];
        }

        if (null === $configuration) {
            // YAML file is empty (but may contain comments)
            $configuration = [];
        }

        if (!is_array($configuration)) {
            throw new InvalidOptionsException(sprintf('Invalid content type in "%s".', $filename));
        }

        return $configuration;
    }

    private function parseJsonConfiguration(string $filename): array
    {
        if (!is_file($filename)) {
            throw new RuntimeException(sprintf('File "%s" does not exist.', $filename));
        }

        if (!is_readable($filename)) {
            throw new RuntimeException(sprintf('File "%s" cannot be read.', $filename));
        }

        try {
            $configuration = json_decode(file_get_contents($filename), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidOptionsException(
                sprintf('Invalid content type in "%s".', $filename),
                $e->getCode(),
            );
        }

        return $configuration;
    }

    private function parsePhpConfiguration(string $filename): array
    {
        $configuration = require $filename;

        if (!is_array($configuration)) {
            throw new InvalidOptionsException(sprintf('Invalid content type in "%s".', $filename));
        }

        return $configuration;
    }
}
