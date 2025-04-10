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

use Overtrue\PHPLint\Extension\ExtensionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @author Laurent Laville
 * @since Release 10.0.0
 */
interface ApplicationInterface
{
    /**
     * @param ExtensionInterface[] $extensions
     */
    public function addExtensions(array $extensions): void;

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
