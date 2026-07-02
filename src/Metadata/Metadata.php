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

use stdClass;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
abstract class Metadata
{
    protected string $name;
    protected string $value;
    protected ?string $description;

    public function describe(): stdClass
    {
        $metadata = new stdClass();
        $metadata->name = $this->name;
        $metadata->value = $this->value;
        $metadata->description = $this->description;

        return $metadata;
    }

    public static function applicationVersion(): ApplicationVersion
    {
        return new ApplicationVersion();
    }
}
