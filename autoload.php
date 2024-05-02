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

if (class_exists(__NAMESPACE__ . '\Autoload', false) === false) {
    class Autoload
    {
        /**
         * The composer autoloader.
         */
        private static ?\Composer\Autoload\ClassLoader $composerAutoloader = null;

        public static function load(string $class)
        {
            if (self::$composerAutoloader === null) {
                self::$composerAutoloader = require __DIR__ . '/vendor/autoload.php';
            }

            self::$composerAutoloader->loadClass($class);
        }
    }

    spl_autoload_register(__NAMESPACE__ . '\Autoload::load', true, true);
}
