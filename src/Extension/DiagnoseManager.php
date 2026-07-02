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

use Overtrue\PHPLint\Command\DiagnoseCommand;
use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Console\ApplicationInterface;
use Overtrue\PHPLint\Event\AfterCheckingEvent;
use Overtrue\PHPLint\Metadata\ConfigurationSettings;
use Overtrue\PHPLint\Metadata\Metadata;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use stdClass;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;

use function in_array;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
final class DiagnoseManager implements
    ExtensionInterface,
    EventSubscriberInterface
{
    private LoggerInterface $logger;
    private string $whenDiagnosed = '';

    private ConfigurationSettings $metaConfigurationSettings;

    public function getName(): string
    {
        return 'diagnose_manager';  //ExtensionEnum::DIAGNOSE_MANAGER->value;
    }

    // @deprecated : Will be remove from API later
    public static function getCommands(): array
    {
        return [];
    }

    public static function getDefinition(): InputDefinition
    {
        return new InputDefinition([
            new InputOption(
                OptionDefinition::DIAGNOSTIC,
                null,
                InputOption::VALUE_REQUIRED,
                'Control the use of providers to diagnose the system',
                'never'
            )
        ]);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => 'initialize',
            ConsoleEvents::TERMINATE => 'terminate',
            AfterCheckingEvent::class => 'finish',
        ];
    }

    public function initialize(ConsoleCommandEvent $event): void
    {
        $input = $event->getInput();
        $command = $event->getCommand();
        $application = $command->getApplication();

        if ($application instanceof ApplicationInterface) {
            $this->logger = $application->getLogger();
        } else {
            // for future implementation of Symfony/Runtime component that may provide a non-compatible Application instance
            // if final user put a wrong implementation ...
            $this->logger = new NullLogger();
        }

        $this->whenDiagnosed = $input->getParameterOption(
            '--' . OptionDefinition::DIAGNOSTIC,
            DiagnoseEnum::AUTO->value,
            true
        );

        if ($this->whenDiagnosed === DiagnoseEnum::NEVER->value) {
            return;
        }

        $this->logger->notice('The Diagnose Manager launched {kind} diagnostic', ['kind' => $this->whenDiagnosed]);
        //$this->logger->debug(__METHOD__);
        $this->logger->notice(__METHOD__);
    }

    public function finish(AfterCheckingEvent $event): void
    {
        $this->logger->debug(__METHOD__);

        $results = $event->getArgument('results');
        $this->metaConfigurationSettings = Metadata::configurationSettings($results->getContext()['options_used']);
    }

    public function terminate(ConsoleTerminateEvent $event): void
    {
        $this->logger->debug(__METHOD__);

        if ($this->whenDiagnosed === DiagnoseEnum::NEVER->value) {
            return;
        }

        $command = $event->getCommand();

        if (in_array($command->getName(), ['list', 'help', 'diagnose'], true)) {
            // don't print any diagnostic for the "list" and "help" commands
            // avoid duplicated output for the diagnose command
            return;
        }

        $input = $event->getInput();
        $output = $event->getOutput();

        $io = new SymfonyStyle($input, $output);

        try {
            $application = $command->getApplication();

            $diagnoseCommand = new DiagnoseCommand();
            $exitCode = $diagnoseCommand($input, $output, $io, $application, $this->metaConfigurationSettings);

            if ($exitCode === 0) {
                $io->success('The Diagnose Manager has finished successfully.');
            }
        } catch (Throwable $exception) {
            $io->error("The Diagnose Manager has finished with following error:\n" . $exception->getMessage());
            $exitCode = $exception->getCode() > 0 ? $exception->getCode() : 1;
        }

        $this->logger->notice('The Diagnose Manager has terminated its diagnostic with exit code: ' . $exitCode);
    }
}
