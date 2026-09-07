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
use Overtrue\PHPLint\Environment\ModeEnum;
use Overtrue\PHPLint\Extension\ExtensionEnum;
use Overtrue\PHPLint\Metadata\Metadata;
use Overtrue\PHPLint\Metadata\MetadataCollection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

use function array_diff;
use function array_intersect;
use function array_merge;
use function array_values;
use function explode;
use function in_array;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
class ConsoleApplicationRunner
{
    protected static EnvConfigInterface $envConfig;
    protected static InputInterface $input;
    protected static OutputInterface $output;
    protected Application $application;

    public function __construct(
        LoggerInterface $logger,
        EnvConfigInterface $envConfig,
        ?InputInterface $input = null,
        ?OutputInterface $output = null,
    ) {
        self::$envConfig = $envConfig;
        self::$input = $input ?? new ArgvInput();
        self::$output = $output ?? new NullOutput();

        $this->application = new Application($this);
        $this->application->setLogger($logger);

        $definition = $this->application->getDefinition();

        $envName = self::getEnvName();

        $defaultFallback = $envConfig->getDefaultFallback($envName);

        if (!$definition->hasOption('env') && !$definition->hasOption('e') && !$definition->hasShortcut('e')) {
            $definition->addOption(new InputOption(
                'env',
                'e',
                InputOption::VALUE_REQUIRED,
                'The Environment name',
                $envName,
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
                self::getAllowedPlugins(),
            ));
        }

        if (!$definition->hasOption(OptionDefinition::CONFIGURATION) && !$definition->hasOption('c') && !$definition->hasShortcut('c')) {
            $definition->addOption(new InputOption(
                OptionDefinition::CONFIGURATION,
                'c',
                InputOption::VALUE_REQUIRED,
                'Path to configuration file',
                $envConfig->get('config', $defaultFallback),
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

        $defaultCommand = self::hasMode(ModeEnum::LEGACY) ? 'lint' : 'list';

        $singleCommand = ($defaultCommand !== 'list');

        $commandName = $singleCommand ? $defaultCommand : $input->getFirstArgument();

        $dynamicValueResolvers = [
            CoreValueResolver::class => fn() => new CoreValueResolver($this->application, self::$output, $commandName),
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

        $this->application->setDefaultCommand($defaultCommand, $singleCommand);
    }

    public function getApplication(): Application
    {
        return $this->application;
    }

    public static function getEnvConfig(): EnvConfigInterface
    {
        return self::$envConfig;
    }

    public static function getAllowedPlugins(): array
    {
        $envConfig = self::$envConfig;
        $input = self::$input;

        $envName = self::getEnvName();

        $defaultFallback = $envConfig->getDefaultFallback($envName);

        $key = 'allow_plugins';
        $allowPlugins = explode(',', $envConfig->get($key, $defaultFallback));

        if (!self::isFrontendInteractive()) {
            $deniedPlugins = [
                ExtensionEnum::DIAGNOSE_MANAGER->value,
                ExtensionEnum::PROFILE_MANAGER->value,
                ExtensionEnum::PROGRESS_MANAGER->value,
            ];
            $allowPlugins = array_diff($allowPlugins, $deniedPlugins);
        }

        $key = 'default_plugins';
        $defaultPlugins = explode(',', $envConfig->get($key, $defaultFallback));

        if (self::hasMode(ModeEnum::DEVELOP)) {
            $defaultPlugins = $allowPlugins = [
                ExtensionEnum::CACHE_MANAGER->value,
                ExtensionEnum::DIAGNOSE_MANAGER->value,
                ExtensionEnum::OUTPUT_MANAGER->value,
                ExtensionEnum::PROFILE_MANAGER->value,
                ExtensionEnum::PROGRESS_MANAGER->value,
            ];
        }

        if (self::hasMode(ModeEnum::DIAGNOSE)) {
            $defaultPlugins[] = ExtensionEnum::DIAGNOSE_MANAGER->value;
            $allowPlugins[] = ExtensionEnum::DIAGNOSE_MANAGER->value;
        }

        if (self::hasMode(ModeEnum::PROFILE)) {
            $defaultPlugins[] = ExtensionEnum::PROFILE_MANAGER->value;
            $allowPlugins[] = ExtensionEnum::PROFILE_MANAGER->value;
        }

        $extensions = [];

        if (true === $input->hasParameterOption(['--' . OptionDefinition::EXTENSIONS, '-x'], true)) {
            $extensions = (array) $input->getParameterOption(['--' . OptionDefinition::EXTENSIONS, '-x']);
        }

        $extensions = array_merge($defaultPlugins, $extensions);

        return array_values(
            array_intersect($extensions, $allowPlugins)
        );
    }

    public function run(): int
    {
        return $this->application->run(self::$input, self::$output);
    }

    public static function getEnvName(): string
    {
        $envConfig = self::$envConfig;
        $input = self::$input;

        if (true === $input->hasParameterOption(['--env', '-e'], true)) {
            return $input->getParameterOption(['--env', '-e']);
        }
        return $envConfig->get('env', 'dev');
    }

    public static function isFrontendInteractive(): bool
    {
        $envConfig = self::$envConfig;
        $input = self::$input;
        $envName = self::getEnvName();

        $defaultFallback = $envConfig->getDefaultFallback($envName);
        $frontend = $envConfig->get('frontend', $defaultFallback);

        if ($frontend == 'cli' && $input->isInteractive()) {
            return true;
        }
        // all other frontend are considered by design as non-interactive
        return false;
    }

    public static function hasMode(ModeEnum $needle): bool
    {
        $envConfig = self::$envConfig;
        $envName = self::getEnvName();

        $defaultFallback = $envConfig->getDefaultFallback($envName);

        $mode = explode(',', $envConfig->get('mode', $defaultFallback));

        return in_array($needle->value, $mode, true);
    }
}
