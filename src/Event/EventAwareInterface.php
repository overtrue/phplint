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

namespace Overtrue\PHPLint\Event;

use Symfony\Component\Console\Command\Command;
use Symfony\Contracts\EventDispatcher\Event;

interface EventAwareInterface
{
    /**
     * Describes the contextual event and write a line to the current Logger instance (if available)
     *
     * @return array{
     *     command?: Command,
     *     arguments?: non-empty-string|false,
     *     eventType: string,
     *     listener: non-falsy-string,
     *     stopPropagation: 'continue'|'stop',
     *     __section__: string,
     *     __style__: 'event'
     * }
     */
    public function describeEvent(Event $event): array;
}
