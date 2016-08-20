<?php

/*
 * This file is part of the overtrue/phplint.
 *
 * Codes from sebastian\environment
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 */

namespace Overtrue\PHPLint\Command;

/**
 */
class LintScreen
{
    const STDIN  = 0;
    const STDOUT = 1;
    const STDERR = 2;

    /**
     * Returns true if STDOUT supports colorization.
     *
     * This code has been copied and adapted from
     * Symfony\Component\Console\Output\OutputStream.
     *
     * @return bool
     */
    public static function hasColorSupport()
    {
        if (DIRECTORY_SEPARATOR == '\\') {
            return false !== getenv('ANSICON') || 'ON' === getenv('ConEmuANSI') || 'xterm' === getenv('TERM');
        }

        if (!defined('STDOUT')) {
            return false;
        }

        return self::isInteractive(STDOUT);
    }

    /**
     * Returns the number of columns of the terminal.
     *
     * @return int
     */
    public static function getNumberOfColumns()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $columns = 80;

            if (preg_match('/^(\d+)x\d+ \(\d+x(\d+)\)$/', trim(getenv('ANSICON')), $matches)) {
                $columns = $matches[1];
            } elseif (function_exists('proc_open')) {
                $process = proc_open(
                    'mode CON',
                    array(
                        1 => ['pipe', 'w'],
                        2 => ['pipe', 'w']
                    ),
                    $pipes,
                    null,
                    null,
                    ['suppress_errors' => true]
                );

                if (is_resource($process)) {
                    $info = stream_get_contents($pipes[1]);

                    fclose($pipes[1]);
                    fclose($pipes[2]);
                    proc_close($process);

                    if (preg_match('/--------+\r?\n.+?(\d+)\r?\n.+?(\d+)\r?\n/', $info, $matches)) {
                        $columns = $matches[2];
                    }
                }
            }

            return $columns - 1;
        }

        if (!self::isInteractive(self::STDIN)) {
            return 80;
        }

        if (function_exists('shell_exec') && preg_match('#\d+ (\d+)#', shell_exec('stty size'), $match) === 1) {
            if ((int) $match[1] > 0) {
                return (int) $match[1];
            }
        }

        if (function_exists('shell_exec') && preg_match('#columns = (\d+);#', shell_exec('stty'), $match) === 1) {
            if ((int) $match[1] > 0) {
                return (int) $match[1];
            }
        }

        return 80;
    }

    /**
     * Returns if the file descriptor is an interactive terminal or not.
     *
     * @param int|resource $fileDescriptor
     *
     * @return bool
     */
    public static function isInteractive($fileDescriptor = self::STDOUT)
    {
        return function_exists('posix_isatty') && @posix_isatty($fileDescriptor);
    }
}