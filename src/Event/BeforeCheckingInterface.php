<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Event;

/**
 * @author Laurent Laville
 * @since Release 9.0.0
 */
interface BeforeCheckingInterface
{
    /**
     * Called before lint begins to run
     *
     * @param BeforeCheckingEvent<string, string> $event
     */
    public function beforeChecking(BeforeCheckingEvent $event): void;
}
