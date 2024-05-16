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

/*
 * @author Laurent Laville
 * @since Release 9.3.0
 */

require_once __DIR__ . '/sarif_converter/bootstrap.php';

use Bartlett\Sarif\Converter\PhpLintConverter;
use Overtrue\PHPLint\Command\LintCommand;
use Overtrue\PHPLint\Configuration\ConsoleOptionsResolver;
use Overtrue\PHPLint\Event\EventDispatcher;
use Overtrue\PHPLint\Finder;
use Overtrue\PHPLint\Linter;
use Overtrue\PHPLint\Output\SarifOutput;
use Symfony\Component\Console\Input\ArrayInput;

$dispatcher = new EventDispatcher([]);

$arguments = [
    'path' => [__DIR__ . '/../src'],
    '--no-configuration' => true,
];
$command = new LintCommand($dispatcher);
$input = new ArrayInput($arguments, $command->getDefinition());
$configResolver = new ConsoleOptionsResolver($input);

$finder = new Finder($configResolver);
$linter = new Linter($configResolver, $dispatcher);
$results = $linter->lintFiles($finder->getFiles());

// custom serializer factory to make the SARIF output human-readable (see resources into bootstrap.php file)
$factory = new MySerializerFactory();

$converter = new PhpLintConverter($factory);
// or alternative
//$converter = new MyConverter($factory);

$output = new SarifOutput(fopen('php://stdout', 'w'));
$output->setConverter($converter);
$output->format($results);
