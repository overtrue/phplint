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

namespace Overtrue\PHPLint\Runtime;

use Overtrue\PHPLint\Configuration\OptionDefinition;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
class ConsoleApplicationRunner
{
    public function __construct(
        protected Application $application,
        protected ?string $defaultEnv,
        protected ?InputInterface $input = null,
        protected ?OutputInterface $output = null,
    ) {
        $definition = $this->application->getDefinition();

        if (!$definition->hasOption(OptionDefinition::BOOTSTRAP) && !$definition->hasOption('b') && !$definition->hasShortcut('b')) {
            $definition->addOption(
                new InputOption(
                    OptionDefinition::BOOTSTRAP,
                    'b',
                    InputOption::VALUE_REQUIRED,
                    'PHP script that is included before the application run',
                    OptionDefinition::DEFAULT_BOOTSTRAP,
                )
            );
        }

        if (!$definition->hasOption(OptionDefinition::EXTENSIONS) && !$definition->hasOption('x') && !$definition->hasShortcut('x')) {
            $definition->addOption(new InputOption(
                    OptionDefinition::EXTENSIONS,
                    'x',
                    InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                    'Allows to change/extend features easily with one or more extensions',
                )
            );
        }

        if (!$definition->hasOption(OptionDefinition::CONFIGURATION) && !$definition->hasOption('c') && !$definition->hasShortcut('c')) {
            $definition->addOption(
                new InputOption(
                    OptionDefinition::CONFIGURATION,
                    'c',
                    InputOption::VALUE_REQUIRED,
                    'Path to configuration file',
                    OptionDefinition::DEFAULT_CONFIG_FILE,
                )
            );
            // @todo Will be removed in next API version
            // (that will only support "--configuration never" to disable the feature)
            $definition->addOption(
                new InputOption(
                    OptionDefinition::NO_CONFIGURATION,
                    null,
                    InputOption::VALUE_NONE,
                    'Ignore default configuration file (<comment>Deprecated option, use "--configuration never" instead</comment>)',
                )
            );
        }

        if (!$definition->hasOption('env') && !$definition->hasOption('e') && !$definition->hasShortcut('e')) {
            $definition->addOption(new InputOption(
                'env',
                'e',
                InputOption::VALUE_REQUIRED,
                'The Environment name',
                $this->defaultEnv,
            ));
        }
    }

    public function getApplication(): Application
    {
        return $this->application;
    }

    public function run(): int
    {
        return $this->application->run($this->input, $this->output);
    }
}
