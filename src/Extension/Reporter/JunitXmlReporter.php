<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Extension\Reporter;

use DateTime;
use DOMDocument;
use DOMElement;
use Overtrue\PHPLint\Extension\Reporter;

use function count;
use function file_put_contents;

/**
 * @author Laurent Laville
 * @since Release 7.0.0
 */
final class JunitXmlReporter extends Reporter
{
    public function format($data, string $filename): void
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;

        $rootElement = $document->createElement('testsuites');
        $document->appendChild($rootElement);

        $suite = new DOMElement('testsuite');
        $rootElement->appendChild($suite);
        $suite->setAttribute('name', 'PHP Linter');
        $suite->setAttribute('timestamp', (new DateTime())->format(DateTime::ISO8601));
        $suite->setAttribute('time', $this->context['time_usage']);
        $suite->setAttribute('tests', '1');
        $suite->setAttribute('errors', (string) count($data));

        $testCase = new DOMElement('testcase');
        $suite->appendChild($testCase);
        $testCase->setAttribute('errors', (string) count($data));
        $testCase->setAttribute('failures', '0');

        foreach ($data as $errorName => $value) {
            $error = $testCase->ownerDocument->createElement('error', $errorName);
            $testCase->appendChild($error);
            $error->setAttribute('type', 'Error');
            $error->setAttribute('message', $value['error']);
        }

        file_put_contents($filename, $document->saveXML());
    }
}
