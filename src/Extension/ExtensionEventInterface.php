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

use Overtrue\PHPLint\Event\AfterCheckingEvent;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
interface ExtensionEventInterface
{
    /**
     * Steps to prepare an extension
     */
    public function initialize(ConsoleCommandEvent $event): void;

    /**
     * When extension has finished its job !
     */
    public function finish(AfterCheckingEvent $event): void;
}
