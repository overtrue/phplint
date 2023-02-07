<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Configuration;

class ConsoleOptionsResolver extends AbstractOptionsResolver
{
    public function factory(): Options
    {
        return new OptionsFactory($this->defaults);
    }
}
