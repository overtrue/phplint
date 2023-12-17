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

$autoloader = 'vendor/autoload.php';

if (Phar::running()) {
    $phar = new Phar($_SERVER['argv'][0]);
    $possibleAutoloadPaths = [
        'phar://' . $phar->getAlias(),
    ];
} else {
    $possibleAutoloadPaths = [
        // local dev repository
        dirname(__DIR__),
        // dependency
        dirname(__DIR__, 4),
    ];
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
