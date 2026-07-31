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

use Overtrue\PHPLint\Command\ProfileCommand;
use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Console\ApplicationInterface;
use Overtrue\PHPLint\Event\AfterCheckingEvent;
use Overtrue\PHPLint\Event\BeforeCheckingEvent;
use Overtrue\PHPLint\Event\Events;
use Overtrue\PHPLint\Metadata\Metadata;
use Overtrue\PHPLint\Metadata\MetadataCollection;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Throwable;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
final class ProfileManager implements
    ExtensionInterface,
    EventSubscriberInterface,
    LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const PROFILING_EVENT = 'profiling';
    public const LINT_FILES_EVENT = 'lint-files';

    private MetadataCollection $metadataCollection;

    private string $whenProfiled;

    private ?Stopwatch $stopwatch = null;

    public function getName(): string
    {
        return ExtensionEnum::PROFILE_MANAGER->value;
    }

    public static function getDefinition(): InputDefinition
    {
        return new InputDefinition([
            new InputOption(
                OptionDefinition::PROFILE,
                null,
                InputOption::VALUE_OPTIONAL,
                'Display timing and memory usage information',
                'auto'
            ),
        ]);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => ['initialize', 200],  // must be initialized before OutputManager (priority: 100)
            ConsoleEvents::TERMINATE => ['terminate', 10], // must be terminated after OutputManager (priority: 100)
            Events::BEFORE_CHECKING => 'beforeExecute',
            Events::AFTER_CHECKING => 'afterExecute',
        ];
    }

    public function initialize(ConsoleCommandEvent $event): void
    {
        $this->logger->debug(__METHOD__);

        $command = $event->getCommand();
        if ($command->getName() !== 'lint') {
            // this extension must be only available for lint command
            return;
        }

        $input = $event->getInput();
        $application = $command->getApplication();

        $this->stopwatch = $application->getProfiler();

        $this->whenProfiled = $input->getOption(OptionDefinition::PROFILE) ?? 'auto';

        if ('never' !== $this->whenProfiled) {
            // when symfony/stopwatch package is installed, start to use it !
            $this->stopwatch?->start(self::PROFILING_EVENT);
        }

        $this->metadataCollection = $application->getMetadata();
    }

    /**
     * Profile the linter processes (start)
     */
    public function beforeExecute(BeforeCheckingEvent $event): void
    {
        $this->logger->debug(__METHOD__);

        $this->stopwatch?->start(self::LINT_FILES_EVENT);
    }

    /**
     * Profile the linter processes (end)
     */
    public function afterExecute(AfterCheckingEvent $event): void
    {
        $this->logger->debug(__METHOD__);

        if ($this->stopwatch?->isStarted(self::LINT_FILES_EVENT)) {
            $this->stopwatch?->stop(self::LINT_FILES_EVENT);
        }
    }

    public function terminate(ConsoleTerminateEvent $event): void
    {
        $this->logger->debug(__METHOD__);

        if (null === $this->stopwatch) {
            // when symfony/stopwatch package is not installed
            return;
        }

        if (!$this->stopwatch?->isStarted(self::PROFILING_EVENT)) {
            return;
        }

        $this->stopwatch?->stop(self::PROFILING_EVENT);

        // adds the profiler analysis results
        $this->metadataCollection->add(Metadata::profilerResults($this->stopwatch));

        $command = $event->getCommand();
        $input = $event->getInput();
        $output = $event->getOutput();

        $io = new SymfonyStyle($input, $output);

        try {
            /** @var ApplicationInterface $application */
            $application = $command->getApplication();

            $profileCommand = new ProfileCommand();
            $exitCode = $profileCommand($input, $output, $io, $this->whenProfiled, $application->getLogger(), $this->metadataCollection);
        } catch (Throwable $exception) {
            $io->error("The Diagnose Manager has finished with following error:\n" . $exception->getMessage());
            $exitCode = $exception->getCode() > 0 ? $exception->getCode() : 1;
        }

        $this->logger->notice('The Profile Manager has terminated its execution with exit code: ' . $exitCode);
    }
}
