<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Output;

/**
 * @author Laurent Laville
 */
interface OutputInterface
{
    public function format(LinterOutput $results): void;
}
