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

use Bartlett\Sarif\Factory\PhpSerializerFactory;
use Bartlett\Sarif\Serializer\Encoder\EncoderInterface;
use Bartlett\Sarif\Serializer\Encoder\PhpJsonEncoder;

/*
 * Learn more to known how to customize the PHPLint SARIF converter
 * @see https://github.com/llaville/sarif-php-sdk/tree/1.2.0/examples/converters/phplint#how-to-customize-your-converter
 *
 * @author Laurent Laville
 * @since Release 9.3.0
 */
class MySerializerFactory extends PhpSerializerFactory
{
    public function createEncoder($realEncoder = null): EncoderInterface
    {
        $realEncoder = new PhpJsonEncoder(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        return parent::createEncoder($realEncoder);
    }
}
