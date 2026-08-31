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

use Overtrue\PHPLint\Command\DiagnoseCommand;
use Overtrue\PHPLint\Command\LintCommand;
use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Configuration\Resolver\CoreValueResolver;
use Overtrue\PHPLint\Configuration\Resolver\DefaultArgumentResolver;
use Overtrue\PHPLint\Configuration\Resolver\DefaultValueResolver;
use Overtrue\PHPLint\Configuration\Resolver\MetadataValueResolver;
use Overtrue\PHPLint\Console\Application;
use Overtrue\PHPLint\Environment\EnvConfigInterface;
use Overtrue\PHPLint\Metadata\Metadata;
use Overtrue\PHPLint\Metadata\MetadataCollection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

use function array_intersect;
use function array_merge;
use function array_values;
use function explode;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
class ConsoleApplicationRunner
{
    protected Application $application;

    public function __construct(
        LoggerInterface $logger,
        EnvConfigInterface $envConfig,
        protected ?InputInterface $input = null,
        protected ?OutputInterface $output = null,
    ) {
        $this->application = new Application($envConfig);
        $this->application->setLogger($logger);

        $definition = $this->application->getDefinition();

        if (!$definition->hasOption('env') && !$definition->hasOption('e') && !$definition->hasShortcut('e')) {
            $definition->addOption(new InputOption(
                'env',
                'e',
                InputOption::VALUE_REQUIRED,
                'The Environment name',
                $envConfig->get('env', 'dev'),
            ));
        }

        if (!$definition->hasOption(OptionDefinition::BOOTSTRAP) && !$definition->hasOption('b') && !$definition->hasShortcut('b')) {
            $definition->addOption(new InputOption(
                OptionDefinition::BOOTSTRAP,
                'b',
                InputOption::VALUE_REQUIRED,
                'PHP script that is included before the application run',
                OptionDefinition::DEFAULT_BOOTSTRAP,
            ));
        }

        if (!$definition->hasOption(OptionDefinition::EXTENSIONS) && !$definition->hasOption('x') && !$definition->hasShortcut('x')) {
            $definition->addOption(new InputOption(
                OptionDefinition::EXTENSIONS,
                'x',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Allows to change/extend features easily with one or more extensions',
                self::getAllowedPlugins($envConfig, $input),
            ));
        }

        if (!$definition->hasOption(OptionDefinition::CONFIGURATION) && !$definition->hasOption('c') && !$definition->hasShortcut('c')) {
            $definition->addOption(new InputOption(
                OptionDefinition::CONFIGURATION,
                'c',
                InputOption::VALUE_REQUIRED,
                'Path to configuration file',
                OptionDefinition::DEFAULT_CONFIG_FILE,
            ));
            // @todo Will be removed in next API version
            // (that will only support "--configuration never" to disable the feature)
            $definition->addOption(new InputOption(
                OptionDefinition::NO_CONFIGURATION,
                null,
                InputOption::VALUE_NONE,
                'Ignore default configuration file (<comment>Deprecated option, use "--configuration never" instead</comment>)',
            ));
        }

        $applicationVersion = Metadata::applicationVersion();

        $this->application->setVersion($applicationVersion->getVersion());

        $metadataCollection = new MetadataCollection(
            $applicationVersion,
        );

        $this->application->setMetadata($metadataCollection);

        $defaultCommand = $envConfig->get('mode', 'off') === 'legacy' ? 'lint' : 'list';

        $dynamicValueResolvers = [
            CoreValueResolver::class => fn() => new CoreValueResolver($this->application, $this->output ?? new NullOutput(), $defaultCommand),
            MetadataValueResolver::class => fn() => new MetadataValueResolver($this->application)
        ];

        $argumentResolver = new DefaultArgumentResolver(
            [],
            new DefaultValueResolver($logger, $envConfig, $dynamicValueResolvers),
        );
        $argumentResolver->setLogger($logger);
        $this->application->setArgResolver($argumentResolver);

        $this->application->addCommands([
            new DiagnoseCommand(),
            new LintCommand(),
        ]);

        $this->application->setDefaultCommand($defaultCommand, ($defaultCommand !== 'list'));
    }

    public function getApplication(): Application
    {
        return $this->application;
    }

    public static function getAllowedPlugins(EnvConfigInterface $envConfig, InputInterface $input): array
    {
        $envName = $envConfig->get('env', 'dev');

        $defaultFallback = $envConfig->getDefaultFallback($envName);

        $key = 'allow_plugins';
        $allowPlugins = explode(',', $envConfig->get($key, $defaultFallback[$key]));

        $key = 'default_plugins';
        $defaultPlugins = explode(',', $envConfig->get($key, $defaultFallback[$key]));

        $extensions = [];

        if (true === $input->hasParameterOption(['--' . OptionDefinition::EXTENSIONS, '-x'], true)) {
            $extensions = (array) $input->getParameterOption(OptionDefinition::EXTENSIONS);
        }

        $extensions = array_merge($defaultPlugins, $extensions);

        return array_values(
            array_intersect($extensions, $allowPlugins)
        );
    }

    public function run(): int
    {
        return $this->application->run($this->input, $this->output);
    }
}
