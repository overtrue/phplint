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

namespace Overtrue\PHPLint\Output;

use DateTime;
use DOMDocument;
use DOMElement;
use DOMException;
use Overtrue\PHPLint\Metadata\ApplicationVersion;
use Overtrue\PHPLint\Metadata\MetadataCollection;
use Overtrue\PHPLint\Metadata\ProfilerOutput;
use Symfony\Component\Console\Output\StreamOutput;

use function count;

/**
 * @author Laurent Laville
 * @since Release 9.0.0
 */
final class JunitOutput extends StreamOutput implements OutputInterface
{
    public function getName(): string
    {
        return 'junit';
    }

    /**
     * @throws DOMException
     */
    public function format(
        LinterOutput $results,  // @deprecated since release 9.8.0, and will be removed in next API version
        MetadataCollection $metadataCollection
    ): void {
        /** @var \Overtrue\PHPLint\Metadata\LinterOutput $results */
        $results = $metadataCollection->getMetadata(\Overtrue\PHPLint\Metadata\LinterOutput::class);

        if (null === $results) {
            // no result available
            return;
        }

        $applicationVersion = $metadataCollection->getMetadata(ApplicationVersion::class);
        $appName = 'PHP Linter';

        if (null !== $applicationVersion) {
            $appName .= ' ' . $applicationVersion->getVersion();
        }

        $profiling = $metadataCollection->getMetadata(ProfilerOutput::class);

        if (null === $profiling) {
            // no profile info available
            $timeUsage = '';
        } else {
            $timeUsage = $profiling->getTimeUsage();
        }

        $failures = $results->getFailures();

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = $this->isVerbose();

        $rootElement = $document->createElement('testsuites');
        $document->appendChild($rootElement);

        $suite = new DOMElement('testsuite');
        $rootElement->appendChild($suite);
        $suite->setAttribute('name', $appName);
        $suite->setAttribute('timestamp', (new DateTime())->format(DateTime::ISO8601));
        $suite->setAttribute('time', $timeUsage);
        $suite->setAttribute('tests', '1');
        $suite->setAttribute('errors', (string) count($failures));

        $testCase = new DOMElement('testcase');
        $suite->appendChild($testCase);
        $testCase->setAttribute('errors', (string) count($failures));
        $testCase->setAttribute('failures', '0');

        foreach ($failures as $errorName => $value) {
            $error = $testCase->ownerDocument->createElement('error', $errorName);
            $testCase->appendChild($error);
            $error->setAttribute('type', 'Error');
            $error->setAttribute('message', $value['error']);
        }

        $this->write($document->saveXML(), false, self::OUTPUT_RAW);
    }
}
