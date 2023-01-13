<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Event;

/**
 * @author Laurent Laville
 * @since Release 7.0.0
 */
interface AfterCheckingInterface
{
    /**
     * Called after lint is completed
     *
     * @param AfterCheckingEvent<string, string> $event
     */
    public function afterChecking(AfterCheckingEvent $event): void;
}
