<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Configuration;

/**
 * @author Laurent Laville
 * @since Release 9.0.0
 */
interface Resolver
{
    public function factory(): Options;

    public function getOptions(): array;

    public function getOption(string $name): mixed;
}
