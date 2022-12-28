<?php

namespace Overtrue\PHPLint\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;

class Application extends BaseApplication
{
    public const NAME = 'phplint';

    public const VERSION = '4.5';

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

    protected function getCommandName(InputInterface $input): string
    {
        return self::NAME;
    }
}
