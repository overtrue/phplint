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
use Overtrue\PHPLint\Configuration\Resolver\LoggerValueResolver;
use Overtrue\PHPLint\Configuration\Resolver\MetadataValueResolver;
use Overtrue\PHPLint\Console\ApplicationInterface;
use Overtrue\PHPLint\Console\Attribute\ValueResolver;
use Overtrue\PHPLint\Console\SectionEnum;
use Overtrue\PHPLint\Environment\EnvConfig;
use Overtrue\PHPLint\Environment\Provider\CI;
use Overtrue\PHPLint\Environment\Provider\Cpu;
use Overtrue\PHPLint\Environment\Provider\DotEnv;
use Overtrue\PHPLint\Environment\Provider\Git;
use Overtrue\PHPLint\Environment\Provider\Metadata;
use Overtrue\PHPLint\Environment\Provider\Php;
use Overtrue\PHPLint\Environment\Provider\Uname;
use Overtrue\PHPLint\Environment\ProviderData;
use Overtrue\PHPLint\Environment\ProviderInterface;
use Overtrue\PHPLint\Environment\Supplier;
use Overtrue\PHPLint\Extension\DiagnoseEnum;
use Overtrue\PHPLint\Metadata\MetadataCollection;
use Psr\Log\LoggerInterface;
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
use function get_debug_type;
use function in_array;
use function is_iterable;
use function json_decode;
use function json_encode;
use function sprintf;
use function str_starts_with;
use function substr;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;

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
        #[ValueResolver(LoggerValueResolver::class)]
        LoggerInterface $logger,
        #[ValueResolver(MetadataValueResolver::class)]
        MetadataCollection $metadataCollection,
    ): int {
        $whenDiagnosed = $input->getOption(OptionDefinition::DIAGNOSTIC) ?? DiagnoseEnum::NEVER->value;

        if (($whenDiagnosed === DiagnoseEnum::NEVER->value) || $output->isQuiet()) {
            return Command::SUCCESS;
        }

        $user = false;
        $filters = [];

        if ($whenDiagnosed === DiagnoseEnum::ALWAYS->value) {
            $vcs = $php = $uname = $ci = $cpu = $dotenv = $metadata = true;
        } else { //
            if ($whenDiagnosed === DiagnoseEnum::AUTO->value) {
                $envConfig = $application instanceof ApplicationInterface ? $application->getEnvConfig() : new EnvConfig();
                $what = explode(',', $envConfig->get('diagnostic', 'metadata:current_configuration'));
            } else {
                $what = explode(',', $whenDiagnosed);
            }
            $parts = [];
            foreach ($what as $pos => $part) {
                if (str_starts_with($part, 'metadata:')) {
                    $filters[] = substr($part, 9);
                    $parts[] = 'metadata';
                } else {
                    $parts[] = $part;
                }
            }

            $vcs = $php = $uname = $ci = $cpu = $dotenv = $metadata = false;

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
                    if ($part == DiagnoseEnum::CPU->value) {
                        $cpu = true;
                    }
                    if ($part == DiagnoseEnum::DOTENV->value) {
                        $dotenv = true;
                    }
                    if ($part == DiagnoseEnum::METADATA->value) {
                        $metadata = true;
                    } else {
                        if (class_exists($part)) {
                            $user = new $part();
                            if (!$user instanceof ProviderInterface) {
                                $user = false;
                            }
                        }
                    }
                }
            }
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
        if ($cpu) {
            $environment->addProvider(new Cpu());
        }
        if ($dotenv) {
            $environment->addProvider(new DotEnv());
        }
        if ($metadata) {
            $environment->addProvider(new Metadata($metadataCollection, $filters));
        }
        if ($user) {
            $environment->addProvider($user);
        }

        $providerData = $environment->describe();

        if (count($providerData) === 0) {
            return 127;
        }

        foreach ($providerData as $providerId => $values) {
            $title = match ($providerId) {
                Git::class => 'VCS Information',
                Php::class => 'PHP Information',
                Uname::class => 'OS Information',
                CI::class => 'CI Information',
                Cpu::class => 'CPU Information',
                DotEnv::class => 'Environment Variables Information',
                Metadata::class => 'Metadata Information',
                default => sprintf('"%s" Information ', $providerId),
            };

            if ($output->isDebug()) {
                $title .= sprintf(' (%s)', $providerId);
            }

            if (!is_iterable($values)) {
                $logger->warning(
                    'Provider {provider_id} not correctly implemented or is not applicable, skip it !',
                    ['provider_id' => $providerId]
                );
                continue;
            }

            $io->section($title);

            $formatter = new FormatterHelper();

            foreach ($values as $providerData) {
                if (!$providerData instanceof ProviderData) {
                    $logger->warning(
                        'Provider {provider_id} not correctly implemented. Expected {provider_data} but got {unexpected_data}, skip it !',
                        [
                            'provider_id' => $providerId,
                            'provider_data' => ProviderData::class,
                            'unexpected_data' => get_debug_type($providerData)
                        ]
                    );
                    continue;
                }
                $info = $providerData->describe();
                $value = $output->isVerbose()
                    ? json_encode(json_decode($info['value']), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                    : $info['value']
                ;

                $style = SectionEnum::ENVIRONMENT->value;
                // when style is not available, fallback to default style defined by \Overtrue\PHPLint\Console\Application::run
                $style = $output->getFormatter()->hasStyle($style) ? $style : SectionEnum::DEFAULT->value;

                $io->writeln(
                    $formatter->formatSection(
                        $info['setting'],
                        sprintf('<comment>%s</comment> %s', $info['description'], $value),
                        $style,
                    )
                );
            }

            $io->newLine();
        }

        return Command::SUCCESS;
    }
}
