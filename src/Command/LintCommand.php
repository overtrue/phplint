<?php

/*
 * This file is part of the overtrue/phplint.
 *
 * (c) 2016 overtrue <i@overtrue.me>
 */

namespace Overtrue\PHPLint\Command;

use Overtrue\PHPLint\Linter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Helper;
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
                'Path to file or directory to lint'
            )
            ->addOption(
               'exclude',
               null,
               InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
               'Path to file or directory to exclude from linting'
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
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startTime = microtime(true);
        $startMemUsage = memory_get_usage(true);

        $output->writeln($this->getApplication()->getLongVersion()." by overtrue and contributors.\n");

        $phpBinary = PHP_BINARY;
        $path = $input->getArgument('path');
        $exclude = $input->getOption('exclude');
        $extensions = $input->getOption('extensions');
        $procLimit = $input->getOption('jobs');

        $extensions = $extensions ? explode(',', $extensions) : ['php'];

        $linter = new Linter($path, $exclude, $extensions);

        $files = $linter->getFiles();
        $fileCount = count($files);

        if ($fileCount <= 0) {
            $output->writeln('<info>Could not find files to lint</info>');

            return 0;
        }

        if ($procLimit) {
            $linter->setProcessLimit($procLimit);
        }

        if (file_exists(__DIR__.'/../../.phplint-cache')) {
            $linter->setCache(json_decode(file_get_contents(__DIR__.'/../../.phplint-cache'), true));
        }

        $progress = new ProgressBar($output, $fileCount);
        $progress->setBarWidth(50);
        $progress->setBarCharacter('<info>.</info>');
        $progress->setEmptyBarCharacter('<comment>.</comment>');
        $progress->setProgressCharacter('|');
        $progress->setMessage('', 'filename');
        $progress->setFormat("%filename%\n\n%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%\n");
        $progress->start();

        $linter->setProcessCallback(function ($status, $filename) use ($progress) {
            $progress->setMessage("<info>Checking: </info>{$filename}", 'filename');
            $progress->advance();
        });

        $errors = $linter->lint($files);
        $progress->finish();

        $timeUsage = Helper::formatTime(microtime(true) - $startTime);
        $memUsage = Helper::formatMemory(memory_get_usage(true) - $startMemUsage);

        $code = 0;
        $errCount = count($errors);

        $output->writeln("\nTime: {$timeUsage}, Memory: {$memUsage}MB\n");

        if ($errCount > 0) {
            $output->writeln('<error>FAILURES!</error>');
            $output->writeln("<error>Files: {$fileCount}, Failures: {$errCount}</error>");
            $this->showErrors($errors, $output);
            $code = 1;
        } else {
            $output->writeln("<info>OK! (Files: {$fileCount}, Success: {$fileCount})</info>");
        }

        return $code;
    }

    /**
     * Show errors detail.
     *
     * @param array                                             $errors
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function showErrors($errors, $output)
    {
        $i = 0;
        $output->writeln("\nThere was ".count($errors).' errors:');

        foreach ($errors as $filename => $error) {
            $output->writeln('<comment>'.++$i.". {$filename}:".'</comment>');
            $error = preg_replace('~in\s+'.preg_quote($filename).'~', '', $error);
            $output->writeln("<error>{$error}</error>");
        }
    }
}
