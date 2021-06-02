<?php

namespace Overtrue\PHPLint\Process;

use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\Process\Process;

class Lint extends Process
{
    public function hasSyntaxError(): bool
    {
        $output = trim($this->getOutput());

        return !str_contains($output, 'No syntax errors detected');
    }

    public function getSyntaxError(): bool|array
    {
        if ($this->hasSyntaxError()) {
            $out = explode("\n", trim($this->getOutput()));

            return $this->parseError(array_shift($out));
        }

        return false;
    }

    #[ArrayShape(['error' => "string", 'line' => "float|int"])]
    public function parseError(string $message): array
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

    public function hasSyntaxIssue(): bool
    {
        $output = trim($this->getOutput());

        return (bool)preg_match('/(Warning:|Deprecated:|Notice:)/', $output);
    }

    public function getSyntaxIssue(): bool|array
    {
        if ($this->hasSyntaxIssue()) {
            $out = explode("\n", trim($this->getOutput()));

            return $this->parseIssue(array_shift($out));
        }

        return false;
    }

    #[ArrayShape(['error' => "string", 'line' => "float|int"])]
    private function parseIssue($message): array
    {
        $pattern = '/^(PHP\s+)?(Warning|Deprecated|Notice):\s*?(?<error>.+?)\s+in\s+.+?\s*line\s+(?<line>\d+)/';

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
