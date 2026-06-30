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

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
enum ProgressEnum: string
{
    case AUTO = 'auto';
    case BAR = 'bar';
    case DOTS = 'dots';
    case INDICATOR = 'indicator';
    case PLAIN = 'plain';
    case NEVER = 'never';
    case QUIET = 'quiet';
}
