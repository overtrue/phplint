<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Process;

/**
 * @author Laurent Laville
 * @since Release 7.0.0
 */
final class LintProcessItem
{
    protected bool $hasSyntaxError = false;
    protected bool $hasSyntaxWarning = false;
    protected string $message;
    protected int $line;
    public function hasSyntaxError(): bool
    {
        return $this->hasSyntaxError;
    }
    public function hasSyntaxWarning(): bool
    {
        return $this->hasSyntaxWarning;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getLine(): int
    {
        return $this->line;
    }
}
