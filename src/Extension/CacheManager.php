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

namespace Overtrue\PHPLint\Extension;

use Overtrue\PHPLint\Cache;
use Overtrue\PHPLint\Configuration\FileOptionsResolver;
use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Event\AfterCheckingEvent;
use Overtrue\PHPLint\Event\Events;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use function get_class;
use function is_object;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
final class CacheManager implements
    ExtensionInterface,
    EventSubscriberInterface,
    LoggerAwareInterface
{
    use LoggerAwareTrait;

    private static Cache $cache;

    public function __construct(private readonly ?AdapterInterface $adapter = null)
    {
        $adapter ??= new NullAdapter();
        self::$cache = new Cache($adapter);
    }

    public function getName(): string
    {
        return ExtensionEnum::CACHE_MANAGER->value;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => ['initialize', 90],
            Events::AFTER_CHECKING => ['afterExecute', 90],
        ];
    }

    public static function getDefinition(): InputDefinition
    {
        return new InputDefinition([
            new InputOption(
                OptionDefinition::CACHE_ADAPTER,
                null,
                InputOption::VALUE_REQUIRED,
                'Adapter ' .
                ' (<info>auto, never, Filesystem, Apcu, ...</info>)',

            ),
            new InputOption(
                OptionDefinition::CACHE_DIR,
                null,
                InputOption::VALUE_REQUIRED,
                'Path to the cache directory'
            ),
            new InputOption(
                OptionDefinition::CACHE_TTL,
                null,
                InputOption::VALUE_REQUIRED,
                'Limit cached data for a period of time'
                . ' (<info>>0: time to live in seconds</info>)',
                OptionDefinition::DEFAULT_CACHE_TTL
            ),
            new InputOption(
                OptionDefinition::NO_CACHE,
                null,
                InputOption::VALUE_NONE,
                'Ignore cached data (<comment>Deprecated option, use "--cache-adapter never" instead</comment>)'
            )
        ]);
    }

    public function initialize(ConsoleCommandEvent $event): void
    {
        $this->logger->debug(__METHOD__);

        $input = $event->getInput();
        $configResolver = new FileOptionsResolver($input);

        $withoutCache = $configResolver->getOption(OptionDefinition::NO_CACHE);
        $defaultLifetime = $configResolver->getOption(OptionDefinition::CACHE_TTL);
        $cacheDir = $configResolver->getOption(OptionDefinition::CACHE_DIR);
        $adapterAlias = $configResolver->getOption(OptionDefinition::CACHE_ADAPTER);

        if (!$withoutCache && $adapterAlias === 'never') {
            $withoutCache = true;
        }

        if ($withoutCache) {
            $adapter = new NullAdapter();
        } else {
            $adapter = $this->adapter ?? $adapterAlias ?? new FilesystemAdapter('paths', $defaultLifetime, $cacheDir);
        }
        self::$cache = new Cache($adapter);

        $this->logger->notice(
            'Cache initialized with "{adapter}" adapter"',
            ['adapter' => is_object($adapter) ? get_class($adapter): $adapter]
        );

        if ($defaultLifetime < OptionDefinition::DEFAULT_CACHE_TTL) {
            self::$cache->clear();
        }
    }

    public function afterExecute(AfterCheckingEvent $event): void
    {
        $this->logger->debug(__METHOD__);

        self::$cache->prune();
    }

    public static function getCacheInstance(): Cache
    {
        return self::$cache;
    }
}
