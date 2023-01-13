<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Extension;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Laurent Laville
 * @since Release 7.0.0
 */
abstract class Reporter
{
    protected InputInterface $input;
    protected OutputInterface $output;
    protected array $context;

    public function __construct(InputInterface $input, OutputInterface $output, array $context)
    {
        $this->input = $input;
        $this->output = $output;
        $this->context = $context;
    }

    abstract public function format($data, string $filename): void;
}
