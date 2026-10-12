<?php

declare(strict_types=1);

/*
 * This file is part of the overtrue/phplint package
 *
 * (c) overtrue
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\PHPLint\Extension;

use Overtrue\PHPLint\Event\EventAwareInterface;
use Overtrue\PHPLint\Event\EventAwareTrait;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

use Symfony\Component\Console\Event\ConsoleEvent;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
abstract class AbstractManager implements LoggerAwareInterface, EventAwareInterface
{
    use LoggerAwareTrait;
    use EventAwareTrait;

    protected function allowEvent(ConsoleEvent $event): bool
    {
        return $event->getCommand()?->getName() === 'lint';
    }
}
