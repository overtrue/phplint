<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Output;

use Symfony\Component\Console\Output\StreamOutput;

use function array_merge;
use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;

/**
 * @author Laurent Laville
 */
final class JsonOutput extends StreamOutput implements OutputInterface
{
    public function format(LinterOutput $results): void
    {
        $failures = $results->getFailures();
        $context = $results->getContext();

        $result = [
            'status' => empty($failures) ? 'success' : 'failure',
            'failures' => $failures,
        ];

        $jsonString = json_encode(
            array_merge($result, $context),
            JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );

        $this->write($jsonString, true);
        fclose($this->getStream());
    }
}
