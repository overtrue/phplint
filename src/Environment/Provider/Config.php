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

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
class Config implements ProviderInterface
{
    public function __construct(private readonly array $settings = [])
    {
    }

    public function describe(): ?array
    {
        $data = [];

        foreach ($this->settings as $setting => $value) {
            $data[] = $this->providerData($setting, $value);
        }

        return $data;
    }

    protected function providerData(string $setting, mixed $value): ProviderData
    {
        return new ProviderData($setting, json_encode($value, JSON_UNESCAPED_SLASHES));
    }
}
