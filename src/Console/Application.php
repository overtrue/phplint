<?php

/*
 * This file is part of the overtrue/phplint
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\PHPLint\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class Application.
 */
class Application extends BaseApplication
{
    const NAME = 'phplint';

    const VERSION = '2.0.2';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(self::NAME, self::VERSION);
    }

    /**
     * Overridden so that the application doesn't expect the command
     * name to be the first argument.
     */
    public function getDefinition()
    {
        $inputDefinition = parent::getDefinition();
        // clear out the normal first argument, which is the command name
        $inputDefinition->setArguments();

        return $inputDefinition;
    }

    /**
     * Gets the name of the command based on input.
     *
     * @param InputInterface $input The input interface
     *
     * @return string The command name
     */
    protected function getCommandName(InputInterface $input)
    {
        return 'phplint';
    }
}
