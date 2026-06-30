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
use Symfony\Component\Process\ExecutableFinder;

use function extension_loaded;
use function php_ini_loaded_file;
use function php_sapi_name;
use function phpversion;
use function sprintf;
use function str_replace;
use function strtolower;

use const PHP_BUILD_DATE;
use const PHP_BUILD_PROVIDER;
use const PHP_DEBUG;
use const PHP_INT_SIZE;
use const PHP_VERSION;
use const PHP_ZTS;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
class Php implements ProviderInterface
{
    public function __construct(
        private ?ExecutableFinder $exeFinder = null,
    ) {
        $this->exeFinder ??= new ExecutableFinder();
    }

    public function describe(): ?array
    {
        $binaryPath = $this->exeFinder->find('php', null);
        if (null === $binaryPath) {
            return null;
        }

        $data = [
            new ProviderData('binary_path', $binaryPath, 'The PHP interpreter binary path'),
            new ProviderData('sapi', php_sapi_name() ?? 'UNKNOWN', 'The server API for this build of the PHP interpreter'),
            new ProviderData('version', PHP_VERSION, 'The PHP interpreter'),
            new ProviderData('php_64bit', (PHP_INT_SIZE === 8 ? 'yes' : 'no'), 'The PHP interpreter, with 64 bit support'),
            new ProviderData('debug', (PHP_DEBUG ? 'yes' : 'no'), 'The PHP interpreter, with debugging symbols'),
            new ProviderData('zts', (PHP_ZTS ? 'yes' : 'no'), 'The PHP interpreter, with Zend Thread Safety'),
            new ProviderData(
                'build_date',
                defined('PHP_BUILD_DATE') ? PHP_BUILD_DATE : 'UNKNOWN',
                'The PHP interpreter build date'
            ),
            new ProviderData(
                'build_provider',
                defined('PHP_BUILD_PROVIDER') ? PHP_BUILD_PROVIDER : 'UNKNOWN',
                'The provider who build the PHP interpreter'
            ),
            new ProviderData('ini', php_ini_loaded_file() ?: 'none', 'Path to the loaded "php.ini" file'),
        ];

        foreach (['dom', 'json', 'mbstring', 'xdebug', 'Zend OPcache'] as $extension) {
            $data[] = new ProviderData(
                'ext-' . strtolower(str_replace(' ', '-', $extension)),
                extension_loaded($extension) ? (phpversion($extension) ?: 'UNKNOWN') : 'not loaded',
                sprintf('The %s PHP extension', $extension)
            );
        }

        return $data;
    }
}
