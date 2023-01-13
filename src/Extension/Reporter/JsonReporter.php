<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Extension\Reporter;

use Overtrue\PHPLint\Extension\Reporter;

use function array_merge;
use function file_put_contents;
use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;

/**
 * @author Laurent Laville
 * @since Release 7.0.0
 */
final class JsonReporter extends Reporter
{
    public function format($data, string $filename): void
    {
        $result = [
            'status' => empty($data) ? 'success' : 'failure',
            'errors' => $data,
        ];

        $jsonString = json_encode(
            array_merge($result, $this->context),
            JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        ) . \PHP_EOL;

        file_put_contents($filename, $jsonString);
    }
}
