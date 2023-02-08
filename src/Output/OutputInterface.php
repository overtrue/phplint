<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Output;

/**
 * @author Laurent Laville
 * @since Release 9.0.0
 */
interface OutputInterface
{
    public function format(LinterOutput $results): void;
}
