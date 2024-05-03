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

namespace Overtrue\PHPLint\Output;

use Bartlett\Sarif\Converter\ConverterInterface;
use Bartlett\Sarif\Converter\PhpLintConverter;

use Symfony\Component\Console\Output\StreamOutput;

use function fclose;

/**
 * @author Laurent Laville
 * @since Release 9.2.0
 */
class SarifOutput extends StreamOutput implements OutputInterface
{
    private ConverterInterface $converter;

    public function setConverter(ConverterInterface $converter): void
    {
        $this->converter = $converter;
    }

    public function format(LinterOutput $results): void
    {
        $converter = $this->converter ?? new PhpLintConverter();

        $jsonString = $converter->format($results);  // @phpstan-ignore-line

        $this->write($jsonString, true);
        fclose($this->getStream());
    }
}
