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

namespace Overtrue\PHPLint\Command;

use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Console\ApplicationInterface;
use Overtrue\PHPLint\Environment\EnvConfig;
use Overtrue\PHPLint\Environment\Provider\CI;
use Overtrue\PHPLint\Environment\Provider\Config;
use Overtrue\PHPLint\Environment\Provider\DotEnv;
use Overtrue\PHPLint\Environment\Provider\Git;
use Overtrue\PHPLint\Environment\Provider\Php;
use Overtrue\PHPLint\Environment\Provider\Uname;
use Overtrue\PHPLint\Environment\ProviderData;
use Overtrue\PHPLint\Environment\ProviderInterface;
use Overtrue\PHPLint\Environment\Supplier;
use Overtrue\PHPLint\Extension\DiagnoseEnum;
use Overtrue\PHPLint\Metadata\ConfigurationSettings;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function class_exists;
use function count;
use function explode;
use function in_array;
use function is_iterable;
use function json_decode;
use function sprintf;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
#[AsCommand(name: 'diagnose', description: 'Diagnoses the system to provide useful information for issue reports')]
final class DiagnoseCommand
{
    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        SymfonyStyle $io,
        Application $application,
        ConfigurationSettings $metaConfigurationSettings,
    ): int {
        $whenDiagnosed = $input->getParameterOption(
            '--' . OptionDefinition::DIAGNOSTIC,
            DiagnoseEnum::AUTO->value,
            true
        );

        if (($whenDiagnosed === DiagnoseEnum::NEVER->value) || $output->isQuiet()) {
            return Command::SUCCESS;
        }

        if ($whenDiagnosed === DiagnoseEnum::ALWAYS->value) {
            $vcs = $php = $uname = $ci = $dotenv = $config = true;
        } else { //
            if ($whenDiagnosed === DiagnoseEnum::AUTO->value) {
                $envConfig = new EnvConfig('phplint');
                $parts = explode(',', $envConfig->get('diagnostic', 'config'));
            } else {
                $parts = explode(',', $whenDiagnosed);
            }

            $vcs = $php = $uname = $ci = $dotenv = $config = false;

            if (!in_array(DiagnoseEnum::NEVER->value, $parts, true)) {
                foreach ($parts as $part) {
                    if ($part == DiagnoseEnum::VCS->value) {
                        $vcs = true;
                    }
                    if ($part == DiagnoseEnum::PHP->value) {
                        $php = true;
                    }
                    if ($part == DiagnoseEnum::UNAME->value) {
                        $uname = true;
                    }
                    if ($part == DiagnoseEnum::CI->value) {
                        $ci = true;
                    }
                    if ($part == DiagnoseEnum::DOTENV->value) {
                        $dotenv = true;
                    }
                    if ($part == DiagnoseEnum::CONFIG->value) {
                        $config = true;
                    }
                }
            }
        }

        if ($application instanceof ApplicationInterface) {
            $logger = $application->getLogger();
        } else {
            // for future implementation of Symfony/Runtime component that may provide a non-compatible Application instance
            // if final user put a wrong implementation ...
            $logger = new NullLogger();
        }

        $environment = new Supplier($logger);

        if ($vcs) {
            $environment->addProvider(new Git());
        }
        if ($php) {
            $environment->addProvider(new Php());
        }
        if ($uname) {
            $environment->addProvider(new Uname());
        }
        if ($ci) {
            $environment->addProvider(new CI());
        }
        if ($dotenv) {
            $environment->addProvider(new DotEnv());
        }
        if ($config) {
            $settings = json_decode($metaConfigurationSettings->describe()->value, true);
            $environment->addProvider(new Config($settings));
        }
        if (!$vcs && !$php && !$uname && class_exists($whenDiagnosed)) {
            $user = new $whenDiagnosed();
            if ($user instanceof ProviderInterface) {
                $environment->addProvider($user);
            }
        }

        $formatter = new FormatterHelper();

        $providerData = $environment->describe();

        if (count($providerData) === 0) {
            $logger->notice('The diagnose command did not produced any results');
            return 127;
        }

        foreach ($providerData as $providerId => $values) {
            $title = match ($providerId) {
                Git::class => 'VCS Information',
                Php::class => 'PHP Information',
                Uname::class => 'OS Information',
                CI::class => 'CI Information',
                DotEnv::class => 'Environment Variables Information',
                Config::class => 'Configuration Information',
                default => sprintf('"%s" Information ', $providerId),
            };

            if (!is_iterable($values)) {
                $logger->warning(
                    'Provider {provider_id} not correctly implemented or is not applicable, skip it !',
                    ['provider_id' => $providerId]
                );
                continue;
            }

            $io->section($title);

            foreach ($values as $providerData) {
                if (!$providerData instanceof ProviderData) {
                    $logger->warning(
                        'Provider {provider_id} not correctly implemented. Expected {provider_data} but got {unexpected_data}, skip it !',
                        [
                            'provider_id' => $providerId,
                            'provider_data' => ProviderData::class,
                            'unexpected_data' => \get_debug_type($providerData)
                        ]
                    );
                    continue;
                }
                $info = $providerData->describe();
                $io->writeln($formatter->formatSection(
                    $info['setting'],
                    sprintf('%s <comment>%s</comment>', $info['value'], $info['description'])
                ));
            }

            $io->newLine();
        }

        return Command::SUCCESS;
    }
}
