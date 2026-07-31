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

use Overtrue\PHPLint\Configuration\FormatEnum;
use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Configuration\Resolver;
use Symfony\Component\Console\Output\ConsoleOutputInterface as SymfonyConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface as SymfonyOutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

use function array_key_exists;
use function array_values;
use function class_exists;
use function fopen;

use const STDOUT;

/**
 * @author Laurent Laville
 * @since Release 9.4.0
 */
final class FormatResolver
{
    private const FORMATTERS = [
        FormatEnum::CHECKSTYLE->value => CheckstyleOutput::class,
        FormatEnum::TXT->value => ConsoleOutput::class,
        FormatEnum::JSON->value => JsonOutput::class,
        FormatEnum::JUNIT->value => JunitOutput::class,
        FormatEnum::SARIF->value => SarifOutput::class,
    ];

    /**
     * @return OutputInterface[]
     */
    public function resolve(
        Resolver $configResolver,
        SymfonyOutputInterface $output,
    ): array {
        $decorated = $output->isDecorated();

        $filename = $configResolver->getOption(OptionDefinition::OUTPUT_FILE);
        if ($filename) {
            $stream = fopen($filename, 'w');
            $decorated = false;
        } else {
            $errOutput = $output instanceof SymfonyConsoleOutputInterface ? $output->getErrorOutput() : $output;
            if ($errOutput instanceof StreamOutput) {
                $stream = $errOutput->getStream();
            } else {
                $stream = STDOUT;
            }
        }

        $requestedFormats = $configResolver->getOption(OptionDefinition::OUTPUT_FORMAT);

        $handlers = [];

        foreach ($requestedFormats as $requestedFormat) {
            if (array_key_exists($requestedFormat, self::FORMATTERS)) {
                // use built-in formatter
                $formatterClass = self::FORMATTERS[$requestedFormat];
                if ($requestedFormat === 'console') {
                    $formatter = new $formatterClass($output->getVerbosity(), $decorated, $output->getFormatter());
                } else {
                    $formatter = new $formatterClass($stream, $output->getVerbosity(), $decorated, $output->getFormatter());
                }
                $handlers[$formatter->getName()] = $formatter;
                continue;
            }

            if (class_exists($requestedFormat)) {
                // try to load custom/external formatter
                $formatter = new $requestedFormat($stream, $output->getVerbosity(), $decorated, $output->getFormatter());

                if (!$formatter instanceof OutputInterface) {
                    // skip invalid instance that does not implement contract
                    continue;
                }
                $handlers[$formatter->getName()] = $formatter;
            }
        }

        // Be sure to always have ($defaultHandler) console output printed first
        // (@see \Overtrue\PHPLint\Output\ChainOutput::format)
        $defaultHandler = OptionDefinition::DEFAULT_FORMATS[0];
        if (isset($handlers[$defaultHandler])) {
            $consoleHandler = $handlers[$defaultHandler];
            unset($handlers[$defaultHandler]);

            $handlers = array_values($handlers);
            $handlers[] = $consoleHandler;
        }

        return $handlers;
    }
}
