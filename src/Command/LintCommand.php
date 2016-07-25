<?php

/*
 * This file is part of the overtrue/phplint.
 *
 * (c) 2016 overtrue <i@overtrue.me>
 */

namespace Overtrue\PHPLint\Command;

use Overtrue\PHPLint\Linter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class LintCommand.
 */
class LintCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('phplint')
            ->setDescription('Lint something')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'Path to a php file or directory to lint'
            )
            ->addOption(
               'exclude',
               null,
               InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
               'Path to a php file or directory to exclude from linting'
            )
            ->addOption(
               'extensions',
               null,
               InputOption::VALUE_REQUIRED,
               'Check only files with selected extensions (default: php)'
            )
            ->addOption(
               'jobs',
               'j',
               InputOption::VALUE_REQUIRED,
               'Number of parraled jobs to run (default: 5)'
            )
            // ->addOption(
            //    'fail-on-first',
            //    null,
            //    InputOption::VALUE_NONE,
            //    'Exit with non zero code on first lint error'
            // )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startTime = microtime(true);

        $output->writeln($this->getApplication()->getLongVersion());
        $output->writeln('');

        $phpBinary = PHP_BINARY;
        $path = $input->getArgument('path');
        $exclude = $input->getOption('exclude');
        $extensions = $input->getOption('extensions');
        $procLimit = $input->getOption('jobs');
        // $failOnFirst = $input->getOption('fail-on-first');

        if ($extensions) {
            $extensions = explode(',', $extensions);
        } else {
            $extensions = ['php'];
        }

        $linter = new Linter($path, $exclude, $extensions);

        $files = $linter->getFiles();
        $fileCount = count($files);

        if ($fileCount > 0) {
            if ($procLimit) {
                $linter->setProcessLimit($procLimit);
            }

            if (file_exists(__DIR__.'/../../phplint.cache')) {
                $linter->setCache(json_decode(file_get_contents(__DIR__.'/../../phplint.cache'), true));
            }

            $progress = new ProgressBar($output, $fileCount);
            $progress->setBarWidth(50);
            $progress->setMessage('', 'filename');
            $progress->setFormat(" %filename%\n %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%");
            $progress->start();

            $linter->setProcessCallback(function ($status, $filename) use ($progress) {
                $progress->setMessage($filename, 'filename');
                $progress->advance();

                // if ($status == 'ok') {
                //     $overview .= '.';
                // } elseif ($status == 'error') {
                //     $overview .= 'F';
                //     // exit(1);
                // }
            });

            $result = $linter->lint($files);

            $progress->finish();
            $output->writeln('');
            $output->writeln('');

            $testTime = microtime(true) - $startTime;

            $code = 0;
            $errCount = count($result);
            $out = "<info>Checked {$fileCount} files in ".round($testTime, 1).' seconds</info>';
            if ($errCount > 0) {
                $out .= "<info> and found syntax errors in </info><error>{$errCount}</error><info> files.</info>";
                $out .= "\n".json_encode($result, JSON_PRETTY_PRINT);
                $code = 1;
            } else {
                $out .= '<info> a no syntax error were deteced.';
            }
            $output->writeln($out);

            return $code;
        }

        $output->writeln('<info>Could not find files to lint</info>');

        return 0;
    }
}
