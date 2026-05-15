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

use RuntimeException;

use function basename;
use function class_exists;
use function dirname;
use function file_exists;
use function glob;
use function implode;
use function spl_autoload_register;
use function sprintf;

use const DIRECTORY_SEPARATOR;

foreach (glob(__DIR__ . '/vendor-bin/*/vendor/autoload.php') as $autoloadFile) {
    require $autoloadFile;
}

if (class_exists(__NAMESPACE__ . '\Autoload', false) === false) {
    class Autoload
    {
        /**
         * The composer autoloader(s).
         */
        private static ?\Composer\Autoload\ClassLoader $composerAutoloader = null;
        private static ?\Composer\Autoload\ClassLoader $optionalAutoloader = null;

        public static function load(string $class): void
        {
            if (self::$composerAutoloader === null) {
                if (isset($GLOBALS['_composer_autoload_path'])) {
                    $possibleAutoloadPaths = [
                        dirname($GLOBALS['_composer_autoload_path'])
                    ];
                    $autoloader = basename($GLOBALS['_composer_autoload_path']);
                } else {
                    $possibleAutoloadPaths = [
                        // local dev repository
                        __DIR__,
                        // dependency
                        dirname(__DIR__, 3),
                    ];
                    $autoloader = 'vendor/autoload.php';
                }

                self::$composerAutoloader = require self::getAutoloadFile($possibleAutoloadPaths, $autoloader);
            }

            $classLoaded = self::$composerAutoloader->loadClass($class);

            if ($classLoaded === true) {
                return;
            }

            self::$optionalAutoloader?->loadClass($class);
        }

        private static function getAutoloadFile(array $possibleAutoloadPaths, string $autoloader): string
        {
            foreach ($possibleAutoloadPaths as $possibleAutoloadPath) {
                if (file_exists($possibleAutoloadPath . DIRECTORY_SEPARATOR . $autoloader)) {
                    return $possibleAutoloadPath . DIRECTORY_SEPARATOR . $autoloader;
                }
            }

            throw new RuntimeException(
                sprintf(
                    'Unable to find "%s" in "%s" paths.',
                    $autoloader,
                    implode('", "', $possibleAutoloadPaths)
                )
            );
        }
    }

    spl_autoload_register(__NAMESPACE__ . '\Autoload::load', true, true);
}
