<?php

use Overtrue\PHPLint\Command\DiagnoseCommand;
use Overtrue\PHPLint\Environment\Provider;
use Overtrue\PHPLint\Environment\ProviderData;
use Overtrue\PHPLint\Environment\ProviderInterface;
use Overtrue\PHPLint\Environment\Supplier;
use Overtrue\PHPLint\Extension\DiagnoseEnum;
use Overtrue\PHPLint\Extension\DiagnoseManager;

function dataSource(): Generator
{
    $classes = [
        DiagnoseManager::class,
        DiagnoseEnum::class,
        DiagnoseCommand::class,
        Supplier::class,
        ProviderInterface::class,
        ProviderData::class,
        Provider\CI::class,
        Provider\Cpu::class,
        Provider\DotEnv::class,
        Provider\Git::class,
        Provider\Metadata::class,
        Provider\Php::class,
        Provider\Uname::class,
    ];
    foreach ($classes as $class) {
        yield $class;
    }
}
