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

namespace Overtrue\PHPLint\Environment\Provider;

use Overtrue\PHPLint\Environment\ProviderData;
use Overtrue\PHPLint\Environment\ProviderInterface;

use function php_uname;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
class Uname implements ProviderInterface
{
    public function describe(): ?array
    {
        return [
            new ProviderData('host', php_uname('n'), 'Host name'),
            new ProviderData('os', php_uname('s'), 'Operating system name'),
            new ProviderData('release', php_uname('r'), 'Release name'),
            new ProviderData('version', php_uname('v'), 'Version information'),
            new ProviderData('architecture', php_uname('m'), 'Machine architecture'),
        ];
    }
}
