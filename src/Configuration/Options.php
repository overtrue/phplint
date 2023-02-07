<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Configuration;

interface Options
{
    public function resolve(): array;
}
