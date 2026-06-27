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

namespace Overtrue\PHPLint\Tests;

use Overtrue\PHPLint\Command\LintCommand;
use Overtrue\PHPLint\Console\Application;

/**
 * @author Laurent Laville
 * @since Release 9.0.0
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected Application $application;

    protected function setUp(): void
    {
        $application = new Application();
        $application->addCommand(new LintCommand());

        $this->application = $application;
    }
}
