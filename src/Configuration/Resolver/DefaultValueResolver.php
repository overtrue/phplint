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
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\ArgumentResolver\ValueResolver\BuiltinTypeValueResolver;
use Symfony\Component\Console\ArgumentResolver\ValueResolver\ValueResolverInterface as SymfonyValueResolverInterface;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
class DefaultValueResolver implements ContainerInterface
{
    protected array $valueResolvers = [];

    public function __construct(LoggerInterface $logger, CoreValueResolver $coreValueResolver)
    {
        $this->valueResolvers = [
            CoreValueResolver::class => $coreValueResolver,
            BuiltinTypeValueResolver::class => fn() => new BuiltinTypeValueResolver(),
            LoggerValueResolver::class => fn() => new LoggerValueResolver($logger),
            MetadataValueResolver::class => fn() => new MetadataValueResolver(),
            PluginValueResolver::class => fn() => new PluginValueResolver(),
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
        ];
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
