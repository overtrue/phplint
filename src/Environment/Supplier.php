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

use Overtrue\PHPLint\Console\SectionEnum;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
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

        $message = sprintf(
            '<comment>%s</comment> %s',
            '"{provider_id}" provider was registered from following filename',
            ': {filename}'
        );

        $this->logger->debug(
            $message,
            [
                '__section__' => SectionEnum::ENVIRONMENT->label(),
                '__style__' => SectionEnum::ENVIRONMENT->value,
                'provider_id' => get_class($provider),
                'filename' => (new ReflectionClass($provider))->getFileName(),
            ]
        );
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
                $message = sprintf(
                    '<comment>%s</comment> %s',
                    '"{provider_id}" did not provided any values',
                    ''
                );
                $this->logger->debug(
                    $message,
                    [
                        '__section__' => SectionEnum::ENVIRONMENT->label(),
                        '__style__' => SectionEnum::ENVIRONMENT->value,
                        'provider_id' => get_class($provider),
                    ]
                );
                continue;
            }
            $data[get_class($provider)] = $values;
        }
        return $data;
    }
}
