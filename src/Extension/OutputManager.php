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

namespace Overtrue\PHPLint\Extension;

use Overtrue\PHPLint\Configuration\FileOptionsResolver;
use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Event\AfterCheckingEvent;
use Overtrue\PHPLint\Event\BeforeCheckingEvent;
use Overtrue\PHPLint\Event\Events;
use Overtrue\PHPLint\Metadata\MetadataCollection;
use Overtrue\PHPLint\Output\ChainOutput;
use Overtrue\PHPLint\Output\FormatResolver;
use Overtrue\PHPLint\Output\LinterOutput;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Finder\Finder;

/**
 * @author Laurent Laville
 * @since Release 9.0.0 (renamed from OutputFormat to OutputManager since 9.8.0)
 */
final class OutputManager implements
    ExtensionInterface,
    EventSubscriberInterface,
    LoggerAwareInterface
{
    use LoggerAwareTrait;

    private array $handlers = [];

    private MetadataCollection $metadataCollection;

    public function getName(): string
    {
        return ExtensionEnum::OUTPUT_MANAGER->value;
    }

    public static function getDefinition(): InputDefinition
    {
        return new InputDefinition([
            new InputOption(
                OptionDefinition::OUTPUT_FORMAT,
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Format of requested reports'
            ),
            new InputOption(
                OptionDefinition::OUTPUT_FILE,
                'o',
                InputOption::VALUE_REQUIRED,
                'Generate an output to the specified path'
                . ' (<comment>default: ' . OptionDefinition::DEFAULT_STANDARD_OUTPUT_LABEL . '</comment>)'
            )
        ]);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => ['initialize', 100],
            ConsoleEvents::TERMINATE => ['terminate', 100],
            Events::BEFORE_CHECKING => ['beforeExecute', 100],
            Events::AFTER_CHECKING => ['afterExecute', 100],
        ];
    }

    public function initialize(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        if ($command->getName() !== 'lint') {
            // this extension must be only available for lint command
            return;
        }

        $this->logger->debug(__METHOD__);

        $this->metadataCollection = $command->getApplication()->getMetadata();

        $this->handlers = (new FormatResolver())->resolve(
            new FileOptionsResolver($event->getInput()),
            $event->getOutput(),
        );
    }

    public function terminate(ConsoleTerminateEvent $event): void
    {
        $this->logger->debug(__METHOD__);

        $metadataCollection = $this->metadataCollection ?? new MetadataCollection();

        /** @var \Overtrue\PHPLint\Metadata\LinterOutput $results */
        $finalResults = $metadataCollection->getMetadata(\Overtrue\PHPLint\Metadata\LinterOutput::class);

        // Only to keep API Backward Compatible with version 9.7.x
        // Will be removed in next API version
        if (null === $finalResults) {
            // no result available
            $results = new LinterOutput([], new Finder());
        } else {
            $results = new LinterOutput(
                [
                    'errors' => $finalResults->getErrors(),
                    'warnings' => $finalResults->getWarnings(),
                    'hits' => $finalResults->getHits(),
                    'misses' => $finalResults->getMisses(),
                ],
                $finalResults->getFinder()
            );
        }

        $outputHandler = new ChainOutput($this->handlers);
        $outputHandler->format($results, $metadataCollection);
    }

    public function beforeExecute(BeforeCheckingEvent $event): void
    {
        $this->logger->debug(
            __METHOD__ . ' ; file count queued to process: {' . BeforeCheckingEvent::FILE_COUNT . '}',
            [BeforeCheckingEvent::FILE_COUNT => $event->getArgument(BeforeCheckingEvent::FILE_COUNT)]
        );
    }

    public function afterExecute(AfterCheckingEvent $event): void
    {
        $this->logger->debug(__METHOD__);
    }
}
