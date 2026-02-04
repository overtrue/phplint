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

use Symfony\Component\Console\Terminal as ConsoleTerminal;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
final class Terminal
{
    private ConsoleTerminal $terminal;

    /**
     * Creates a new terminal instance.
     */
    public function __construct(?ConsoleTerminal $terminal = null)
    {
        $this->terminal = $terminal ?? new ConsoleTerminal();
    }

    /**
     * Gets the terminal width.
     */
    public function width(): int
    {
        return $this->terminal->getWidth();
    }

    /**
     * Gets the terminal height.
     */
    public function height(): int
    {
        return $this->terminal->getHeight();
    }
}
