<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Event;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @author Laurent Laville
 * @since Release 9.0.0
 */
final class BeforeLintFileEvent extends GenericEvent
{
}
