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

namespace Overtrue\PHPLint\Metadata;

use function json_encode;

use const JSON_UNESCAPED_SLASHES;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
final class ConfigurationSettings extends Metadata
{
    public const METADATA_ID = 'current_configuration';


    public function __construct(private readonly array $settings)
    {
        $this->description = 'Current configuration settings';
        $this->value = json_encode($this->settings, JSON_UNESCAPED_SLASHES);
    }

    public function getConfigFilePath(): string
    {
        return $this->settings['configuration'] ?? '';
    }

    public function hasConfigFile(): bool
    {
        return !($this->settings['no-configuration'] ?? true);
    }
}
