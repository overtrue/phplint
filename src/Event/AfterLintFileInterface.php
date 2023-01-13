<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Event;

/**
 * @author Laurent Laville
 * @since Release 7.0.0
 */
interface AfterLintFileInterface
{
    /**
     * Called after a file has been checked
     *
     * @param AfterLintFileEvent<string, string> $event
     */
    public function afterLintFile(AfterLintFileEvent $event): void;
}
