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

namespace Overtrue\PHPLint\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

interface ApplicationInterface
{
    public function getEventDispatcher(): EventDispatcherInterface;

    public function setDefaultCommand(string $commandName, bool $isSingleCommand = false): static;

    public function getDefaultCommand(): ?Command;

    /**
     * Returns the long version of the application.
     *
     * @return string
     */
    public function getLongVersion();

    public function getVersion(): string;
}
