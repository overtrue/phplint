<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Configuration;

/**
 * @author Laurent Laville
 * @since Release 9.0.0
 */
interface Options
{
    public function resolve(): array;
}
