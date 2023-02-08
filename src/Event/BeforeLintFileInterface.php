<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Event;

/**
 * @author Laurent Laville
 * @since Release 9.0.0
 */
interface BeforeLintFileInterface
{
    /**
     * Called before a file has been checked
     *
     * @param BeforeLintFileEvent<string, string> $event
     */
    public function beforeLintFile(BeforeLintFileEvent $event): void;
}
