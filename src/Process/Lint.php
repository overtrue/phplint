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

            return $this->parseError($out);
        }

        return false;
    }

    /**
     * Parse error message.
     *
     * @param string $message
     *
     * @return array
     */
    public function parseError($message)
    {
        $pattern = '/^Parse error:\s*(?:\w+ error,\s*)?(?<error>.+?)\s+in\s+.+?\s*line\s+(?<line>\d+)/';

        preg_match($pattern, $message, $match);

        $match = array_merge(['error' => 'Unknown', 'line' => 0], $match);

        return [
            'error' => $match['error'],
            'line' => $match['line'],
        ];
    }
}
