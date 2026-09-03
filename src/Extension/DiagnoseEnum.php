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
enum DiagnoseEnum: string
{
    case AUTO = 'auto';
    case ALWAYS = 'always';
    case CI = 'ci';
    case CPU = 'cpu';
    case DOTENV = 'dotenv';
    case METADATA = 'metadata';
    case NEVER = 'never';
    case PHP = 'php';
    case UNAME = 'uname';
    case VCS = 'vcs';
}
