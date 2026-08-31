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
use Overtrue\PHPLint\Console\SectionEnum;
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
final class DiagnoseManager extends AbstractManager implements
    ExtensionInterface,
    EventSubscriberInterface
{
    private string $whenDiagnosed = '';

    public function getName(): string
    {
        return ExtensionEnum::DIAGNOSE_MANAGER->value;
    }

    public static function getDefinition(): InputDefinition
    {
        return new InputDefinition([
            new InputOption(
                OptionDefinition::DIAGNOSTIC,
                null,
                InputOption::VALUE_OPTIONAL,
                'Control the use of providers to diagnose the system',
                DiagnoseEnum::AUTO->value
            )
        ]);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => ['initialize', -100],
            ConsoleEvents::TERMINATE => ['terminate', -100],
        ];
    }

    public function initialize(ConsoleCommandEvent $event): void
    {
        $this->describeEvent($event);

        $input = $event->getInput();

        $this->whenDiagnosed = $input->getOption(OptionDefinition::DIAGNOSTIC) ?? DiagnoseEnum::AUTO->value;

        if ($this->whenDiagnosed === DiagnoseEnum::NEVER->value) {
            return;
        }

        $message = sprintf(
            '<comment>%s</comment> %s',
            'The "Diagnose Manager" launched following diagnostic',
            ': {kind}'
        );

        $this->logger->notice($message, [
            '__section__' => SectionEnum::PLUGIN->label(),
            '__style__' => SectionEnum::PLUGIN->value,
            'kind' => $this->whenDiagnosed,
        ]);
    }

    public function terminate(ConsoleTerminateEvent $event): void
    {
        $this->describeEvent($event);

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
            /** @var ApplicationInterface $application */
            $application = $command->getApplication();

            $metadataCollection = $application->getMetadata();

            $diagnoseCommand = new DiagnoseCommand();
            $exitCode = $diagnoseCommand($input, $output, $io, $application->getLogger(), $metadataCollection, $this->whenDiagnosed);

            if ($exitCode === 0) {
                $io->success('The Diagnose Manager has finished successfully.');
            }
        } catch (Throwable $exception) {
            $io->error("The Diagnose Manager has finished with following error:\n" . $exception->getMessage());
            $exitCode = $exception->getCode() > 0 ? $exception->getCode() : 1;
        }

        $message = sprintf(
            '<comment>%s</comment> %s',
            'The "Diagnose Manager" has terminated its diagnostic with exit code',
            ': {exit_code}'
        );

        $context = [
            '__section__' => SectionEnum::PLUGIN->label(),
            '__style__' => SectionEnum::PLUGIN->value,
            'exit_code' => $exitCode,
        ];

        if ($exitCode > 0) {
            $message .= ' {reason_code}';

            $context['reason_code'] = '(none result produced)';
        }

        $this->logger->notice($message, $context);
    }
}
