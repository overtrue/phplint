<?php

use Overtrue\PHPLint\Extension\ProgressBar;
use Overtrue\PHPLint\Extension\ProgressIndicator;
use Overtrue\PHPLint\Extension\ProgressPrinter;
use Overtrue\PHPLint\Helper\ProgressHelper;
use Symfony\Component\Console\Helper\ProgressIndicator as ProgressIndicatorHelper;

function dataSource(): Generator
{
    $classes = [
        ProgressHelper::class,
        ProgressIndicatorHelper::class,
        ProgressPrinter::class,
        ProgressBar::class,
        ProgressIndicator::class,
    ];
    foreach ($classes as $class) {
        yield $class;
    }
}
