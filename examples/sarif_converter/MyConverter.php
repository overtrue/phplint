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

use Bartlett\Sarif\Converter\PhpLintConverter;
use Bartlett\Sarif\Definition\MultiformatMessageString;
use Bartlett\Sarif\Definition\ToolComponent;
use Composer\InstalledVersions;

/*
 * Learn more to known how to customize the PHPLint SARIF converter
 * @see https://github.com/llaville/sarif-php-sdk/tree/1.2.0/examples/converters/phplint#how-to-customize-your-converter
 *
 * @author Laurent Laville
 * @since Release 9.3.0
 */
class MyConverter extends PhpLintConverter
{
    public function __construct()
    {
        $factory = new MySerializerFactory();
        parent::__construct($factory);
    }

    public function toolExtensions(): array
    {
        $converterPackage = 'bartlett/sarif-php-sdk';
        $converterVersion = InstalledVersions::getVersion($converterPackage);

        $extension = new ToolComponent($converterPackage);
        $extension->setShortDescription(new MultiformatMessageString('PHPLint SARIF Converter'));
        $extension->setVersion($converterVersion);

        return [$extension];
    }
}
