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

namespace Overtrue\PHPLint\Environment;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

use function get_class;
use function sprintf;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
final class Supplier
{
    /**
     * @param array<ProviderInterface>|null $providers List of environment information providers
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private ?array $providers = null,
    ) {
        $this->providers ??= [];
    }

    public function addProvider(ProviderInterface $provider): void
    {
        $this->providers[] = $provider;
        $this->logger->debug(sprintf('Provider "%s" registered', get_class($provider)));
    }

    public function describe(?ProviderInterface $provider = null, ?string $part = null): null|string|array
    {
        $filtered = false;

        if (null !== $provider) {
            foreach ($this->providers as $instance) {
                if ($provider instanceof $instance) {
                    $filtered = true;
                    break;
                }
            }
            if (!$filtered) {
                throw new RuntimeException(sprintf('Provider "%s" is not registered', get_class($provider)));
            }
        }

        if (!$filtered) {
            // returns data of all providers registered
            return $this->__debugInfo();
        }

        $info = $provider->describe();
        if (null === $info) {
            return null;
        }
        return $part ? $info[$part] : $info;
    }

    public function __debugInfo(): array
    {
        $data = [];
        foreach ($this->providers as $provider) {
            if ($provider instanceof LoggerAwareInterface) {
                $provider->setLogger($this->logger);
            }
            $values = $provider->describe();
            if (null === $values) {
                $this->logger->debug(sprintf('[%s] did not provided any values', get_class($provider)));
                continue;
            }
            $data[get_class($provider)] = $values;
        }
        return $data;
    }
}
