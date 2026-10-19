<?php


use Overtrue\PHPLint\Command\ProfileCommand;
use Overtrue\PHPLint\Extension\ProfileManager;

function dataSource(): Generator
{
    $classes = [
        ProfileManager::class,
        ProfileCommand::class,
    ];
    foreach ($classes as $class) {
        yield $class;
    }
}
