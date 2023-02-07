<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Configuration;

interface Resolver
{
    public function factory(): Options;

    public function getOptions(): array;

    public function getOption(string $name): mixed;
}
