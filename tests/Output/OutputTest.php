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

namespace Overtrue\PHPLint\Tests\Output;

use Overtrue\PHPLint\Cache;
use Overtrue\PHPLint\Environment\EnvConfigInterface;
use Overtrue\PHPLint\Finder;
use Overtrue\PHPLint\Linter;
use Overtrue\PHPLint\Metadata\MetadataCollection;
use Overtrue\PHPLint\Output\JunitOutput;
use Overtrue\PHPLint\Output\LinterOutput;
use Overtrue\PHPLint\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function fopen;
use function rewind;

use const DIRECTORY_SEPARATOR;

/**
 * @author Laurent Laville
 * @since Release 9.5.3
 */
#[CoversClass(JunitOutput::class)]
final class OutputTest extends TestCase
{
    private LinterOutput $linterOutput;

    private MetadataCollection $metadataCollection;

    private EnvConfigInterface $envConfig;

    /**
     * @throws Throwable
     */
    protected function setUp(): void
    {
        parent::setUp();

        $basePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'fixtures';

        $finder = new Finder(null, [$basePath], [], ['php']);
        $linter = new Linter(
            cache: new Cache(new NullAdapter()),
            showWarning: true,
        );

        $application = $this->getApplication();
        $this->metadataCollection = $application->getMetadata();
        $this->envConfig = $application->getRunner()->getEnvConfig();

        $this->linterOutput = $linter->lintFiles($finder->getFiles(), null, $this->metadataCollection);
    }

    public function testJunitOutput(): void
    {
        $stream = fopen('php://memory', 'w+');
        $output = new JunitOutput($stream, OutputInterface::VERBOSITY_VERBOSE, false);
        $output->format($this->linterOutput, $this->metadataCollection, $this->envConfig);

        rewind($stream);
        $xml = stream_get_contents($stream);

        $this->assertStringContainsString('syntax_error.php</error>', $xml);
        $this->assertStringContainsString('syntax_warning.php</error>', $xml);
    }
}
