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

namespace Overtrue\PHPLint\Environment\Provider;

use Overtrue\PHPLint\Environment\ProviderData;
use Overtrue\PHPLint\Environment\ProviderInterface;
use Overtrue\PHPLint\Metadata\MetadataCollection;

use function in_array;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
readonly class Metadata implements ProviderInterface
{
    private MetadataCollection $metadataCollection;

    public function __construct(MetadataCollection $metadataCollection, array $filters = [], array $excludes = [])
    {
        $this->metadataCollection = new MetadataCollection();
        foreach ($metadataCollection as $metadata) {
            $name = $metadata->describe()->name;
            if (in_array($name, $excludes, true)) {
                continue;
            }
            if (empty($filters) || in_array($name, $filters, true)) {
                $this->metadataCollection->add($metadata);
            }
        }
    }

    public function describe(): ?array
    {
        $data = [];

        foreach ($this->metadataCollection as $metadata) {
            $dto = $metadata->describe();

            $data[] = new ProviderData($dto->name, $dto->value, $dto->description);
        }

        return $data;
    }
}
