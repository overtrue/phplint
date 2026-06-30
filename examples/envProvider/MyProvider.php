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

use Overtrue\PHPLint\Environment\ProviderData;
use Overtrue\PHPLint\Environment\ProviderInterface;

class MyProvider implements ProviderInterface
{
    public function describe(): ?array
    {
        $data = [
            new ProviderData('user_1', 'def_1', 'desc_1'),
        ];

        if (extension_loaded('Zend OPcache')) {
            // @link https://php.watch/versions/8.4/opcache-jit-ini-default-changes

            $directives = opcache_get_configuration()['directives'];

            $data[] = new ProviderData('opcache_enable', $directives['opcache.enable'] ? 'yes': 'no', 'OPCache enabled');
            $data[] = new ProviderData('opcache_enable_cli', $directives['opcache.enable_cli'] ? 'yes': 'no', 'OPCache enabled in CLI');
            $data[] = new ProviderData('opcache_preload', $directives['opcache.preload'], 'OPCache preloader script');
            $data[] = new ProviderData('opcache_jit', $directives['opcache.jit'], 'OPCache JIT');
        }

        return $data;
    }
}
