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

namespace Overtrue\PHPLint\Console;

use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Logger\ConsoleLogger as SymfonyConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
final class ConsoleLogger extends SymfonyConsoleLogger
{
    private HelperInterface $formatter;

    private OutputInterface $output;
    private HelperInterface $helper;

    public function __construct(OutputInterface $output, array $verbosityLevelMap = [], array $formatLevelMap = [])
    {
        parent::__construct($output, $verbosityLevelMap, $formatLevelMap);
        // mandatory because $output instance of Symfony ConsoleLogger is private
        $this->output = $output;
    }

    public function log($level, $message, array $context = []): void
    {
        $section = $context['__section__'] ?? 'Undefined';
        $defaultStyle = 'section';

        // try to apply custom log message style (if available)
        $style = $context['__style__'] ?? $defaultStyle;

        if (!$this->output->getFormatter()->hasStyle($style)) {
            // fallback to default style that must be declared by \Overtrue\PHPLint\Console\Application::run method
            $style = $defaultStyle;
        }

        parent::log($level, (new FormatterHelper())->formatSection($section, $message, $style), $context);
    }
}
