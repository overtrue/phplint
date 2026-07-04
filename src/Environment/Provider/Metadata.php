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

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
readonly class Metadata implements ProviderInterface
{
    public function __construct(private MetadataCollection $metadataCollection)
    {
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
