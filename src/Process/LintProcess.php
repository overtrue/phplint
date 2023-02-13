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

namespace Overtrue\PHPLint\Process;

use Closure;
use Symfony\Component\Process\Process;

use function array_shift;
use function explode;
use function preg_match;
use function str_contains;
use function trim;

/**
 * @author Laurent Laville
 * @since Release 9.0.0
 */
final class LintProcess extends Process
{
    private static Closure $createLintProcessItem;

    public function __construct(array $command, string $cwd = null, array $env = null, mixed $input = null, ?float $timeout = 60)
    {
        parent::__construct($command, $cwd, $env, $input, $timeout);

        self::$createLintProcessItem = Closure::bind(
            static function (bool $hasError, string $errorString, int $errorLine, bool $hasWarning, string $warningString, int $warningLine) {
                $item = new LintProcessItem();
                $item->hasSyntaxError = $hasError;
                $item->hasSyntaxWarning = $hasWarning;
                if ($hasError) {
                    $item->message = $errorString;
                    $item->line = $errorLine;
                } elseif ($hasWarning) {
                    $item->message = $warningString;
                    $item->line = $warningLine;
                } else {
                    $item->message = '';
                    $item->line = 0;
                }
                return $item;
            },
            null,
            LintProcessItem::class
        );
    }

    public function getItem(string $output): LintProcessItem
    {
        $hasError = !str_contains($output, 'No syntax errors detected');
        $hasWarning = (bool) preg_match('/(Warning:|Deprecated:|Notice:)/', $output);

        $out = explode("\n", trim($output));
        $text = array_shift($out);

        if ($hasError) {
            $pattern = '/^(PHP\s+)?(Parse|Fatal) error:\s*(?:\w+ error,\s*)?(?<error>.+?)\s+in\s+.+?\s*line\s+(?<line>\d+)/';

            $matched = preg_match($pattern, $text, $match);

            if (empty($message)) {
                $message = 'Unknown';
            }
        } else {
            $message = '';
            $matched = false;
        }
        $errorString = $matched ? "{$match['error']} in line {$match['line']}" : $message;
        $errorLine = $matched ? (int) $match['line'] : 0;

        if ($hasWarning) {
            $pattern = '/^(PHP\s+)?(Warning|Deprecated|Notice):\s*?(?<error>.+?)\s+in\s+.+?\s*line\s+(?<line>\d+)/';

            $matched = preg_match($pattern, $text, $match);

            if (empty($message)) {
                $message = 'Unknown';
            }
        } else {
            $message = '';
            $matched = false;
        }
        $warningString = $matched ? "{$match['error']} in line {$match['line']}" : $message;
        $warningLine = $matched ? (int) $match['line'] : 0;

        return (self::$createLintProcessItem)($hasError, $errorString, $errorLine, $hasWarning, $warningString, $warningLine);
    }
}
