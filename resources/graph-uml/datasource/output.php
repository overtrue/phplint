<?php

use Overtrue\PHPLint\Configuration\FormatEnum;
use Overtrue\PHPLint\Extension\OutputManager;
use Overtrue\PHPLint\Output\ChainOutput;
use Overtrue\PHPLint\Output\CheckstyleOutput;
use Overtrue\PHPLint\Output\ConsoleOutput;
use Overtrue\PHPLint\Output\ConsoleOutputInterface;
use Overtrue\PHPLint\Output\FormatResolver;
use Overtrue\PHPLint\Output\JsonOutput;
use Overtrue\PHPLint\Output\JunitOutput;
use Overtrue\PHPLint\Output\LinterOutput;
use Overtrue\PHPLint\Output\OutputInterface;
use Overtrue\PHPLint\Output\SarifOutput;

function dataSource(): Generator
{
    $classes = [
        OutputManager::class,
        FormatEnum::class,
        ChainOutput::class,
        CheckstyleOutput::class,
        ConsoleOutput::class,
        ConsoleOutputInterface::class,
        FormatResolver::class,
        JsonOutput::class,
        JunitOutput::class,
        LinterOutput::class,
        OutputInterface::class,
        SarifOutput::class,
    ];
    foreach ($classes as $class) {
        yield $class;
    }
}
