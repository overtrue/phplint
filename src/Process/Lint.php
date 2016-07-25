<?php

/*
 * This file is part of the overtrue/phplint.
 *
 * (c) 2016 overtrue <i@overtrue.me>
 */

namespace Overtrue\PHPLint\Process;

use Symfony\Component\Process\Process;

/**
 * Class Lint.
 */
class Lint extends Process
{
    /**
     * @return bool
     */
    public function hasSyntaxError()
    {
        return strpos($this->getOutput(), 'No syntax errors detected') === false;
    }

    /**
     * @return bool|string
     */
    public function getSyntaxError()
    {
        if ($this->hasSyntaxError()) {
            list(, $out) = explode("\n", $this->getOutput());

            return $out;
        }

        return false;
    }
}
