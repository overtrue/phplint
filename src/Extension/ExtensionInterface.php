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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;

/**
 * @author Laurent Laville
 * @since Release 10.0.0
 */
interface ExtensionInterface
{
    /**
     * @see https://stackoverflow.com/questions/19901850/how-do-i-get-an-objects-unqualified-short-class-name
     */
    public function getName(): string;

    /**
     * A Command Provider for additional command to add to Application
     *
     * @return Command[]
     */
    public static function getCommands(): array;

    /**
     * Add extra arguments and options provided by an extension to the Console Application lint command
     *
     * @see https://symfony.com/doc/current/components/console/console_arguments.html
     */
    public static function getDefinition(): InputDefinition;
}
