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

use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
interface ApplicationInterface
{
    public function getLogger(): LoggerInterface;

    // API compatibility with Symfony/Console 8.1+
    public function getDispatcher(): ?EventDispatcherInterface;

    /**
     * Returns the long version of the application.
     */
    public function getLongVersion(): string;

    public function getVersion(): string;
}
