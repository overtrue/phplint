<?php

/*
 * This file is part of the overtrue/phplint.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
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
        $output = trim($this->getOutput());

        if (defined('HHVM_VERSION') && empty($output)) {
            return false;
        }

        return false === strpos($output, 'No syntax errors detected');
    }

    /**
     * @return bool|array
     */
    public function getSyntaxError()
    {
        if ($this->hasSyntaxError()) {
            $out = explode("\n", trim($this->getOutput()));

            return $this->parseError(array_shift($out));
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
        $pattern = '/^(PHP\s+)?(Parse|Fatal) error:\s*(?:\w+ error,\s*)?(?<error>.+?)\s+in\s+.+?\s*line\s+(?<line>\d+)/';

        $matched = preg_match($pattern, $message, $match);

        if (empty($message)) {
            $message = 'Unknown';
        }

        return [
            'error' => $matched ? "{$match['error']} in line {$match['line']}" : $message,
            'line' => $matched ? abs($match['line']) : 0,
        ];
    }
}
