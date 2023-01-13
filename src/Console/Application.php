<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Console;

use Overtrue\PHPLint\Command\LintCommand;
use Overtrue\PHPLint\Event\EventDispatcher;
use Overtrue\PHPLint\Extension\ProgressBar;
use Overtrue\PHPLint\Extension\ProgressPrinter;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class Application extends BaseApplication
{
    public const NAME = 'phplint';
    public const VERSION = '7.0-dev';

    private ?EventDispatcherInterface $dispatcher = null;

    public function __construct()
    {
        parent::__construct(self::NAME, self::VERSION);
    }

    public function getDefinition(): InputDefinition
    {
        $inputDefinition = parent::getDefinition();
        // clear out the normal first argument, which is the command name
        $inputDefinition->setArguments();

        return $inputDefinition;
    }

    public function getDispatcher(): ?EventDispatcherInterface
    {
        return $this->dispatcher;
    }

    protected function getCommandName(InputInterface $input): string
    {
        return self::NAME;
    }

    protected function doRunCommand(Command $command, InputInterface $input, OutputInterface $output): int
    {
        if ($command instanceof LintCommand) {
            $extensions = [
                new ProgressPrinter(),
                new ProgressBar(),
            ];
            $this->dispatcher = new EventDispatcher($extensions);
            $this->setDispatcher($this->dispatcher);
        }
        return parent::doRunCommand($command, $input, $output);
    }
}
