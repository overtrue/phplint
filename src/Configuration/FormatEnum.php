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

namespace Overtrue\PHPLint\Configuration;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
enum FormatEnum: string
{
    case CHECKSTYLE = 'checkstyle';
    case JSON = 'json';
    case JUNIT = 'junit';
    case SARIF = 'sarif';
    case TXT = 'console';
}
