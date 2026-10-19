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

namespace Overtrue\PHPLint;

use DirectoryIterator;
use Phar;
use RuntimeException;

use function class_exists;
use function dirname;
use function file_exists;
use function implode;
use function spl_autoload_register;
use function sprintf;

if (class_exists(__NAMESPACE__ . '\Autoload', false) === false) {
    class Autoload
    {
        /**
         * The composer autoloader(s).
         */
        private static ?\Composer\Autoload\ClassLoader $composerAutoloader = null;

        public static function load(string $class): void
        {
            if (self::$composerAutoloader === null) {
                $autoloader = '/vendor/autoload.php';
                $possibleAutoloaderPaths = [
                    // local dev repository
                    __DIR__ . $autoloader,
                    // dependency
                    dirname(__DIR__, 3) . $autoloader,
                ];

                if (isset($GLOBALS['_composer_autoload_path'])) {
                    // @link https://getcomposer.org/doc/articles/vendor-binaries.md#finding-the-composer-autoloader-from-a-binary
                    $possibleAutoloaderPaths[] = $GLOBALS['_composer_autoload_path'];
                }

                // [!CAUTION]
                // https://www.php.net/manual/en/phar.using.stream.php#104320
                $baseDir = Phar::running() ?: __DIR__;

                // checks to register optional autoloader
                if (file_exists($baseDir . '/vendor-bin')) {
                    foreach (new DirectoryIterator($baseDir . '/vendor-bin') as $directory) {
                        if ($directory->isDot()) {
                            continue;
                        }
                        $autoloadFile = $directory->getPathname() . $autoloader;
                        if (file_exists($autoloadFile)) {
                            require $autoloadFile;
                        }
                    }
                }

                self::$composerAutoloader = require self::getAutoloadFile($possibleAutoloaderPaths);
            }

            self::$composerAutoloader->loadClass($class);
        }

        private static function getAutoloadFile(array $possibleAutoloaderPaths): string
        {
            foreach ($possibleAutoloaderPaths as $possibleAutoloaderPath) {
                if (file_exists($possibleAutoloaderPath)) {
                    return $possibleAutoloaderPath;
                }
            }

            throw new RuntimeException(
                sprintf(
                    'Unable to find an autoloader in "%s" paths.',
                    implode('", "', $possibleAutoloaderPaths)
                )
            );
        }
    }

    spl_autoload_register(__NAMESPACE__ . '\Autoload::load', true, true);
}
