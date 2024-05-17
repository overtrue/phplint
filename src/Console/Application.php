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

use Overtrue\PHPLint\Helper\DebugFormatterHelper;
use Overtrue\PHPLint\Helper\ProcessHelper;
use Overtrue\PHPLint\Output\ConsoleOutput;
use Phar;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Command\ListCommand;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function array_keys;
use function in_array;

/**
 * @author Overtrue
 * @author Laurent Laville (since v9.0)
 */
final class Application extends BaseApplication
{
    public const NAME = 'phplint';
    public const VERSION = '9.3.1';

    public function __construct()
    {
        parent::__construct(self::NAME, self::VERSION);
    }

    public function run(InputInterface $input = null, OutputInterface $output = null): int
    {
        $output ??= new ConsoleOutput();

        return parent::run($input, $output);
    }

    protected function configureIO(InputInterface $input, OutputInterface $output): void
    {
        if (Phar::running()) {
            $inputDefinition = $this->getDefinition();
            $inputDefinition->addOption(
                new InputOption(
                    'manifest',
                    null,
                    InputOption::VALUE_NONE,
                    'Show which versions of dependencies are bundled'
                )
            );
        }
        parent::configureIO($input, $output);
    }

    protected function getDefaultCommands(): array
    {
        return [new HelpCommand(), new ListCommand()];
    }

    protected function getDefaultHelperSet(): HelperSet
    {
        return new HelperSet([
            new FormatterHelper(),
            new DebugFormatterHelper(),
            new ProcessHelper(),
        ]);
    }

    protected function getCommandName(InputInterface $input): ?string
    {
        $name = parent::getCommandName($input);
        return in_array($name, array_keys(parent::all())) ? $name : null;
    }
}
