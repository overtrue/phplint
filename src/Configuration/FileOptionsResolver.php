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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

use function is_array;
use function sprintf;

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
            $configuration = $this->parseYamlConfiguration($configFile);
            $configuration[OptionDefinition::CONFIGURATION] = $configFile;
        }

        parent::__construct($input, $configuration);
    }

    public function factory(): Options
    {
        return new OptionsFactory($this->defaults);
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

        foreach ($configuration as $name => $value) {
            if (null === $value) {
                throw new InvalidOptionsException(sprintf('Invalid content type in "%s" for option "%s".', $filename, $name));
            }
        }

        return $configuration;
    }
}
