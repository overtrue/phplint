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
 * @since Release 9.4.0
 */

use Bartlett\Sarif\Converter\PhpLintConverter;
use Overtrue\PHPLint\Cache;
use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Environment\EnvConfig;
use Overtrue\PHPLint\Finder;
use Overtrue\PHPLint\Linter;
use Overtrue\PHPLint\Metadata\MetadataCollection;
use Overtrue\PHPLint\Output\SarifOutput;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

require_once dirname(__DIR__, 2) . '/autoload.php';

$definition = new InputDefinition();

$definition->addOption(new InputOption(
    'output-class',
    null,
    InputOption::VALUE_REQUIRED,
    'Class to output',
    SarifOutput::class,
));
$definition->addOption(new InputOption(
    'converter-class',
    null,
    InputOption::VALUE_REQUIRED,
    'Class to convert data format',
    PhpLintConverter::class,
));
$definition->addOption(new InputOption(
    OptionDefinition::BOOTSTRAP,
    'b',
    InputOption::VALUE_REQUIRED,
    'PHP script that is included before the application run',
));
$definition->addOption(new InputOption(
    '--verbose',
    '-v|vv|vvv',
    InputOption::VALUE_NONE,
    'Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug'
));

$input = new ArgvInput(null, $definition);

if ($argc === 1) {
    echo "Using options:" . PHP_EOL;
    var_export($input->getOptions());
    exit(0);
}

$bootstrap = $input->getOption(OptionDefinition::BOOTSTRAP);

if ($bootstrap && file_exists($bootstrap)) {
    // specify autoloader that should be used to load resources
    require_once $bootstrap;
}

$outputClass = $input->getOption('output-class');

if (empty($outputClass) || !class_exists($outputClass)) {
    // fallback to built-in SARIF output class
    $outputClass = SarifOutput::class;
}

$converterClass = $input->getOption('converter-class');

$converter = null;

if (class_exists($converterClass)) {
    $converter = new $converterClass($input->getOption('verbose'));
}

$sourcePath = [__DIR__ . '/../../src', __DIR__ . '/../../tests'];

$finder = new Finder(null, $sourcePath);
$linter = new Linter(
    cache: new Cache(new NullAdapter()),
);

$metadataCollection = new MetadataCollection();

$results = $linter->lintFiles($finder->getFiles(), null, $metadataCollection);

echo "Convert results with : " . $converterClass . PHP_EOL;

$output = new $outputClass(STDOUT, OutputInterface::VERBOSITY_VERBOSE, null, null, $converter);
if ($output instanceof OutputInterface) {
    $envConfig = new EnvConfig();
    $output->format($results, $metadataCollection, $envConfig);
}
