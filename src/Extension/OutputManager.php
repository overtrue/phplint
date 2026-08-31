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

use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Event\AfterCheckingEvent;
use Overtrue\PHPLint\Event\BeforeCheckingEvent;
use Overtrue\PHPLint\Event\Events;
use Overtrue\PHPLint\Metadata\ConfigurationSettings;
use Overtrue\PHPLint\Metadata\MetadataCollection;
use Overtrue\PHPLint\Output\ChainOutput;
use Overtrue\PHPLint\Output\FormatResolver;
use Overtrue\PHPLint\Output\LinterOutput;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Finder\Finder;

use function json_decode;

/**
 * @author Laurent Laville
 * @since Release 9.0.0 (renamed from OutputFormat to OutputManager since 9.8.0)
 */
final class OutputManager extends AbstractManager implements
    ExtensionInterface,
    EventSubscriberInterface
{
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
        $this->describeEvent($event);

        $command = $event->getCommand();
        if ($command->getName() !== 'lint') {
            // this extension must be only available for lint command
            return;
        }

        $application = $command->getApplication();

        $this->metadataCollection = $application->getMetadata();

        $settings = json_decode(
            $this->metadataCollection->getMetadata(ConfigurationSettings::class)->describe('value'),
            true
        );

        $this->handlers = (new FormatResolver())->resolve(
            null,
            $event->getOutput(),
            $settings[OptionDefinition::OUTPUT_FILE],
            $settings[OptionDefinition::OUTPUT_FORMAT],
        );
    }

    public function terminate(ConsoleTerminateEvent $event): void
    {
        $this->describeEvent($event);

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
        $this->describeEvent($event);
    }

    public function afterExecute(AfterCheckingEvent $event): void
    {
        $this->describeEvent($event);
    }
}
