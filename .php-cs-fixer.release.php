<?php

require_once 'vendor-bin/php-cs-fixer/src/ApplicationVersionFixer.php';

use Overtrue\CodingStandard\Fixer\ApplicationVersionFixer;

return (new PhpCsFixer\Config())
    ->registerCustomFixers([
        new ApplicationVersionFixer(),
    ])
    ->setRules([
        ApplicationVersionFixer::name() => true,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in([__DIR__.'/src/Console'])
    )
;
