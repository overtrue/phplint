<?php

/*
 * This file is part of the overtrue/phplint.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

$header = <<<EOF
This file is part of the overtrue/phplint.

(c) overtrue <i@overtrue.me>

This source file is subject to the MIT license that is bundled
with this source code in the file LICENSE.
EOF;

return PhpCsFixer\Config::create()
    ->setUsingCache(false)
    ->setRiskyAllowed(true)
    // use default SYMFONY_LEVEL and extra fixers:
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'header_comment' => [
            'header' => $header,
        ],
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => true,
        //'strict',
        //'strict_param',
        'phpdoc_order' => true, // 注释中param throw return等的顺序
        'no_php4_constructor' => true, //将同名构造方法改为__construct()
    ])
    ->setFinder(
        PhpCsFixer\Config::create()->getFinder()
            ->exclude('vendor')
            ->in(__DIR__.'/')
            ->append([
                __FILE__,
            ])
    )
;
