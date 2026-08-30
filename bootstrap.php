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

use Overtrue\PHPLint\Console\SectionEnum;
use Overtrue\PHPLint\Output\ConsoleOutput;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

$output = new ConsoleOutput();

$styles = [
    SectionEnum::COMMAND->value => new OutputFormatterStyle('white', 'blue'),
    SectionEnum::ARGUMENT->value => new OutputFormatterStyle('yellow', 'blue'),
    SectionEnum::PLUGIN->value => new OutputFormatterStyle('white', 'red'),
    SectionEnum::PROFILE->value => new OutputFormatterStyle('black', 'gray'),
    SectionEnum::ENVIRONMENT->value => new OutputFormatterStyle('yellow', 'blue'),
    SectionEnum::EVENT->value => new OutputFormatterStyle('white', 'magenta'),
    SectionEnum::METADATA->value => new OutputFormatterStyle('black', 'yellow'),
];

foreach ($styles as $name => $style) {
    if (!$output->getFormatter()->hasStyle($name)) {
        $output->getFormatter()->setStyle($name, $style);
    }
}
