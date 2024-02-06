<?php

declare(strict_types=1);

/*
 * This file is part of the overtrue/phplint package
 *
 * (c) overtrue
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 * @since Release 9.1.x
 * @author Laurent Laville
 */

if (isset($_composer_autoload_path)) {
    $possibleAutoloadPaths = [
        dirname($_composer_autoload_path)
    ];
    $autoloader = basename($_composer_autoload_path);
} else {
    $possibleAutoloadPaths = [
        // local dev repository
        dirname(__DIR__),
        // dependency
        dirname(__DIR__, 4),
    ];
    $autoloader = 'vendor/autoload.php';
}

$isAutoloadFound = false;
foreach ($possibleAutoloadPaths as $possibleAutoloadPath) {
    if (file_exists($possibleAutoloadPath . DIRECTORY_SEPARATOR . $autoloader)) {
        require_once $possibleAutoloadPath . DIRECTORY_SEPARATOR . $autoloader;
        $isAutoloadFound = true;
        $vendorDir = $possibleAutoloadPath . DIRECTORY_SEPARATOR . dirname($autoloader);
        break;
    }
}

if ($isAutoloadFound === false) {
    throw new RuntimeException(
        sprintf(
            'Unable to find "%s" in "%s" paths.',
            $autoloader,
            implode('", "', $possibleAutoloadPaths)
        )
    );
}
