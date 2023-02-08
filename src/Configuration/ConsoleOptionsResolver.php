<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Configuration;

/**
 * @author Laurent Laville
 * @since Release 9.0.0
 */
class ConsoleOptionsResolver extends AbstractOptionsResolver
{
    public function factory(): Options
    {
        return new OptionsFactory($this->defaults);
    }
}
