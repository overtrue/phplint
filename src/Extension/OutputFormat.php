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

use Bartlett\Sarif\Converter\ConverterInterface;
use Overtrue\PHPLint\Command\LintCommand;
use Overtrue\PHPLint\Configuration\ConsoleOptionsResolver;
use Overtrue\PHPLint\Configuration\FileOptionsResolver;
use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Event\AfterCheckingEvent;
use Overtrue\PHPLint\Event\AfterCheckingInterface;
use Overtrue\PHPLint\Output\ChainOutput;
use Overtrue\PHPLint\Output\JsonOutput;
use Overtrue\PHPLint\Output\JunitOutput;
use Overtrue\PHPLint\Output\SarifOutput;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use RuntimeException;

use function class_exists;
use function sprintf;

/**
 * @author Laurent Laville
 * @since Release 9.0.0
 */
class OutputFormat implements EventSubscriberInterface, AfterCheckingInterface
{
    private array $outputOptions;
    private array $handlers = [];

    public function __construct(array $outputOptions = [])
    {
        $this->outputOptions = $outputOptions;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => 'initFormat',
        ];
    }

    public function initFormat(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        if (!$command instanceof LintCommand) {
            // this extension must be only available for lint command
            return;
        }

        $input = $event->getInput();

        if (true === $input->hasParameterOption(['--no-configuration'], true)) {
            $configResolver = new ConsoleOptionsResolver($input);
        } else {
            $configResolver = new FileOptionsResolver($input);
        }

        foreach ($this->outputOptions as $name) {
            if ($filename = $configResolver->getOption($name)) {
                if (OptionDefinition::LOG_JSON == $name) {
                    $this->handlers[] = new JsonOutput(fopen($filename, 'w'));
                } elseif (OptionDefinition::LOG_JUNIT == $name) {
                    $this->handlers[] = new JunitOutput(fopen($filename, 'w'));
                } elseif (OptionDefinition::LOG_SARIF == $name) {
                    $sarifHandler = new SarifOutput(fopen($filename, 'w'));

                    $sarifConverterClass = $configResolver->getOption(OptionDefinition::SARIF_CONVERTER);
                    if (!class_exists($sarifConverterClass)) {
                        throw new RuntimeException(
                            sprintf('Could not load sarif converter class: "%s"', $sarifConverterClass)
                        );
                    }

                    $converter = new $sarifConverterClass();
                    if ($converter instanceof ConverterInterface) {
                        $sarifHandler->setConverter($converter);
                    }

                    $this->handlers[] = $sarifHandler;
                }
            }
        }

        $this->handlers[] = $event->getOutput();
    }

    public function afterChecking(AfterCheckingEvent $event): void
    {
        $outputHandler = new ChainOutput($this->handlers);
        $outputHandler->format($event->getArgument('results'));
    }
}
