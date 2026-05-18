<?php

/*
 * This file is part of the overtrue/phplint package
 *
 * (c) overtrue
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Bartlett\Sarif\Converter\PhpLintConverter;

/*
 * @author Laurent Laville
 * @since Release 9.4.0
 */
class MyPhpLintConverter extends PhpLintConverter
{
    public function __construct(bool $prettyPrint)
    {
        parent::__construct(['format_output' => true]);
    }

    public function configure(array $options = []): void
    {
        $this->toolName = 'My PHPLint';
        $this->toolInformationUri = 'https://github.com/llaville/phplint';
        $this->toolComposerPackage = '';
        $this->toolSemanticVersion = '0.1.0@dev';

        parent::configure($options);
    }
}
