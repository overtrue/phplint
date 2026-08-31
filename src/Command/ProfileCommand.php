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

namespace Overtrue\PHPLint\Command;

use Overtrue\PHPLint\Console\SectionEnum;
use Overtrue\PHPLint\Metadata\CacheOutput;
use Overtrue\PHPLint\Metadata\LinterOutput;
use Overtrue\PHPLint\Metadata\MetadataCollection;
use Overtrue\PHPLint\Metadata\ProfilerOutput;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function get_class;
use function in_array;
use function sprintf;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
#[AsCommand(name: 'profile', description: 'Profile information')]
final class ProfileCommand
{
    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        SymfonyStyle $io,
        string $when,
        LoggerInterface $logger,
        MetadataCollection $metadataCollection,
    ): int
    {
        /** @var ProfilerOutput $profilerResults */
        $profilerResults = $metadataCollection->getMetadata(ProfilerOutput::class);
        if (null === $profilerResults) {
            return 127;
        }

        $title = 'Profiling Information';
        if ($output->isDebug()) {
            $title .= sprintf(' (%s)', get_class($profilerResults));
        }

        /** @var LinterOutput $linterResults */
        $linterResults = $metadataCollection->getMetadata(LinterOutput::class);

        $io->section($title);

        $lines = [
            [
                'time',
                'Initialization time',
                'Bootstrapping time before start source code analysis',
                $profilerResults->getInitializationTimeUsage(),
            ],
            [
                'time',
                'Linter time',
                'Source code analysis time',
                $profilerResults->getTimeUsage(),
            ],
            [
                'memory',
                'Linter memory',
                'Amount of memory allocated to the Linter component',
                $profilerResults->getMemoryUsage(),
            ],
            [
                'time',
                'Total time',
                'PHPLint full execution time',
                $profilerResults->getTotalExecutionTimeUsage(),
            ],
            [
                'memory',
                'Total memory',
                'Amount of memory allocated to PHPLint',
                $profilerResults->getTotalMemoryUsage(),
            ],
            [
                'process',
                'Total process',
                'Process used to scan all source files',
                $linterResults->getProcessCount(),
            ],
        ];

        $formatter = new FormatterHelper();

        $style = SectionEnum::toValue(SectionEnum::PROFILE->value);
        // when style is not available, fallback to default style defined by \Overtrue\PHPLint\Console\Application::run
        $style = $output->getFormatter()->hasStyle($style) ? $style : SectionEnum::DEFAULT->value;

        foreach ($lines as $line) {
            list ($kind, $section, $message, $comment) = $line;

            if (!in_array($when, [$kind, 'auto'])) {
                continue;
            }

            $io->writeln(
                $formatter->formatSection(
                    $section,
                    sprintf('<comment>%s</comment> %s', $message, $comment),
                    $style,
                )
            );
        }

        $cacheResults = $metadataCollection->getMetadata(CacheOutput::class);

        if (null !== $cacheResults && in_array($when, ['cache', 'auto'])) {
            $cacheHits = $cacheResults->hits();
            $cacheMisses = $cacheResults->misses();

            $message = sprintf(
                '%d hit%s, %d miss%s',
                $cacheHits,
                $cacheHits > 1 ? 's' : '',
                $cacheMisses,
                $cacheMisses > 1 ? 'es' : ''
            );
            $comment = $cacheResults->innerAdapterClass();

            $io->writeln(
                $formatter->formatSection(
                    SectionEnum::CACHE->label(),
                    sprintf('<comment>%s</comment> %s', $comment, $message),
                    $style,
                )
            );
        }

        $io->newLine();

        return Command::SUCCESS;
    }
}
