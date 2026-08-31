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

namespace Overtrue\PHPLint\Configuration\Resolver;

use Closure;
use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Environment\EnvConfigInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\ArgumentResolver\ValueResolver\ValueResolverInterface as SymfonyValueResolverInterface;

use function array_merge;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
class DefaultValueResolver implements ContainerInterface
{
    protected array $valueResolvers = [];

    public function __construct(LoggerInterface $logger, EnvConfigInterface $envConfig, array $dynamicValueResolvers = [])
    {
        $this->valueResolvers = array_merge($dynamicValueResolvers, [
            LoggerValueResolver::class => fn() => new LoggerValueResolver($logger),
            PluginValueResolver::class => fn() => new PluginValueResolver($envConfig),
            ConfigValueResolver::class => fn() => new ConfigValueResolver(),
            PathValueResolver::class => fn() => new PathValueResolver(
                [OptionDefinition::PATH, 'sourcePath'],
                [OptionDefinition::EXCLUDE, 'excludePath'],
                [
                    OptionDefinition::PATH => OptionDefinition::DEFAULT_PATH,
                    OptionDefinition::EXCLUDE => OptionDefinition::DEFAULT_EXCLUDES,
                    // aliases
                    'sourcePath' => OptionDefinition::DEFAULT_PATH,
                    'excludePath' => OptionDefinition::DEFAULT_EXCLUDES,
                ]
            ),
            FileExtensionValueResolver::class => fn() => new FileExtensionValueResolver(
                OptionDefinition::DEFAULT_EXTENSIONS,
                [
                    OptionDefinition::FILE_EXTENSIONS => OptionDefinition::DEFAULT_EXTENSIONS,
                    // aliases
                    'fileExtensions' => OptionDefinition::DEFAULT_EXTENSIONS,
                ]
            ),
            JobValueResolver::class => fn() => new JobValueResolver(),
            ShowWarningsValueResolver::class => fn() => new ShowWarningsValueResolver(),
            MemoryLimitValueResolver::class => fn() => new MemoryLimitValueResolver(),
            IgnoreExitCodeValueResolver::class => fn() => new IgnoreExitCodeValueResolver(),
            DryRunValueResolver::class => fn() => new DryRunValueResolver(),
            OutputFormatResolver::class => fn() => new OutputFormatResolver([
                OptionDefinition::OUTPUT_FORMAT => OptionDefinition::DEFAULT_FORMATS,
            ]),
            OutputFileResolver::class => fn() => new OutputFileResolver([
                OptionDefinition::OUTPUT_FILE => OptionDefinition::DEFAULT_STANDARD_OUTPUT
            ]),
        ]);
    }

    final public function get(string $id): null|ValueResolverInterface|SymfonyValueResolverInterface
    {
        $resolver = $this->valueResolvers[$id] ?? null;
        if ($resolver instanceof Closure) {
            $resolver = ($resolver)();
        }

        return $resolver;
    }

    final public function has(string $id): bool
    {
        return isset($this->valueResolvers[$id]);
    }
}
