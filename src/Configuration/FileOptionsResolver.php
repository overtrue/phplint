<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Configuration;

use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\Yaml\Yaml;

use function is_array;
use function sprintf;

/**
 * @author Laurent Laville
 * @since Release 9.0.0
 */
class FileOptionsResolver extends AbstractOptionsResolver
{
    public function __construct(InputInterface $input, InputDefinition $definition)
    {
        $configFile = $input->getOption(OptionDefinition::OPTION_CONFIG_FILE);

        $configuration = Yaml::parseFile($configFile);

        if (null === $configuration) {
            // YAML file is empty (but may contain comments)
            $configuration = [];
        }

        if (!is_array($configuration)) {
            throw new InvalidOptionsException(sprintf('Invalid content type in "%s".', $configFile));
        }

        foreach ($configuration as $name => $value) {
            if (null === $value) {
                throw new InvalidOptionsException(sprintf('Invalid content type in "%s" for option "%s".', $configFile, $name));
            }
        }

        parent::__construct($input, $definition, $configuration);
    }

    public function factory(): Options
    {
        return new OptionsFactory($this->defaults);
    }
}
