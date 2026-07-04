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

namespace Overtrue\PHPLint\Event;

/**
 * Contains all events dispatched by PHPLint.
 *
 * @author Laurent Laville
 * @since Release 9.8.0
 */
final class Events
{
    /**
     * The AFTER_CHECKING event allows you to attach listeners after a file is scanned by the Linter of PHPLint.
     * It provides scanned results and current PHPLint console application identification.
     *
     * @link "\Overtrue\PHPLint\Event\AfterCheckingEvent"
     */
    public const AFTER_CHECKING = 'phplint.after_checking';

    /**
     * The AFTER_LINT_FILE event allows you to attach listeners after a file is scanned by the Linter of PHPLint.
     * It provides scanned result and the file information.
     *
     * @link "\Overtrue\PHPLint\Event\AfterLintFileEvent"
     */
    public const AFTER_LINT_FILE = 'phplint.after_lint_file';

    /**
     * The BEFORE_CHECKING event allows you to attach listeners before a file is scanned by the Linter of PHPLint.
     * It provides number of file queued for the scan.
     *
     * @link "\Overtrue\PHPLint\Event\BeforeCheckingEvent"
     */
    public const BEFORE_CHECKING = 'phplint.before_checking';

    /**
     * The BEFORE_LINT_FILE event allows you to attach listeners before a file is scanned by the Linter of PHPLint.
     * It provides the file information.
     *
     * @link "\Overtrue\PHPLint\Event\BeforeLintFileEvent"
     */
    public const BEFORE_LINT_FILE = 'phplint.before_lint_file';
}
