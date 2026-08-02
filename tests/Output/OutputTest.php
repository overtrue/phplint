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
use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Finder;
use Overtrue\PHPLint\Linter;
use Overtrue\PHPLint\Metadata\MetadataCollection;
use Overtrue\PHPLint\Output\JunitOutput;
use Overtrue\PHPLint\Output\LinterOutput;
use Overtrue\PHPLint\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
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

    /**
     * @throws Throwable
     */
    protected function setUp(): void
    {
        parent::setUp();

        $basePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'fixtures';

        $arguments = [
            OptionDefinition::PATH => [$basePath],
            '--' . OptionDefinition::NO_CONFIGURATION => true,
            '--' . OptionDefinition::WARNING => true,
            '--' . OptionDefinition::FILE_EXTENSIONS => ['php']
        ];

        $configResolver = $this->getOptionsResolver($arguments);

        $finder = new Finder($configResolver);

        $cache = new Cache(new NullAdapter());

        $linter = new Linter($configResolver, new EventDispatcher(), null, null, null, $cache);

        $application = $this->getApplication();
        $this->metadataCollection = $application->getMetadata();

        $this->linterOutput = $linter->lintFiles($finder->getFiles(), null, $this->metadataCollection);
    }

    public function testJunitOutput(): void
    {
        $stream = fopen('php://memory', 'w+');
        $output = new JunitOutput($stream, OutputInterface::VERBOSITY_VERBOSE, false);
        $output->format($this->linterOutput, $this->metadataCollection);

        rewind($stream);
        $xml = stream_get_contents($stream);

        $this->assertStringContainsString('syntax_error.php</error>', $xml);
        $this->assertStringContainsString('syntax_warning.php</error>', $xml);
    }
}
