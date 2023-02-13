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

use Symfony\Component\EventDispatcher\EventDispatcher as SymfonyEventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Laurent Laville
 * @since Release 9.0.0
 */
final class EventDispatcher extends SymfonyEventDispatcher
{
    private array $extensions = [];

    public function __construct(iterable $extensions)
    {
        parent::__construct();

        foreach ($extensions as $extension) {
            if ($extension instanceof EventSubscriberInterface) {
                $this->addSubscriber($extension);
                $this->extensions[] = $extension;
            }
        }
    }

    public function dispatch(object $event, string $eventName = null): object
    {
        $triggered = false;

        foreach ($this->extensions as $extension) {
            if ($extension instanceof BeforeCheckingInterface && $event instanceof BeforeCheckingEvent) {
                $extension->beforeChecking($event);
                $triggered = true;
            } elseif ($extension instanceof AfterCheckingInterface && $event instanceof AfterCheckingEvent) {
                $extension->afterChecking($event);
                $triggered = true;
            } elseif ($extension instanceof BeforeLintFileInterface && $event instanceof BeforeLintFileEvent) {
                $extension->beforeLintFile($event);
                $triggered = true;
            } elseif ($extension instanceof AfterLintFileInterface && $event instanceof AfterLintFileEvent) {
                $extension->afterLintFile($event);
                $triggered = true;
            }
        }

        if ($triggered) {
            return $event;
        }
        return parent::dispatch($event, $eventName);
    }
}
