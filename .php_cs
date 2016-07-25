<?php

$year = date('Y');

$header = <<<EOF
This file is part of the overtrue/phplint.

(c) $year overtrue <i@overtrue.me>
EOF;

Symfony\CS\Fixer\Contrib\HeaderCommentFixer::setHeader($header);

return Symfony\CS\Config\Config::create()
    // use default SYMFONY_LEVEL and extra fixers:
    ->fixers(array(
        'header_comment',
        'short_array_syntax',
        'ordered_use',
        //'strict',
        //'strict_param',
        'phpdoc_order', // 注释中param throw return等的顺序
        'php4_constructor', //将同名构造方法改为__construct()
    ))
    ->finder(
        Symfony\CS\Finder\DefaultFinder::create()
            ->exclude('vendor')
            ->in(__DIR__.'/')
    )
;
