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

namespace Overtrue\PHPLint\Configuration\Resolver;

use Overtrue\PHPLint\Console\Attribute\ReflectionMember;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\RawInputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
readonly class CoreValueResolver implements ValueResolverInterface
{
    public function __construct(
        private Application $application,
        private OutputInterface $output,
        private string $commandName,
    ) {
    }

    public function resolve(string $argumentName, InputInterface $input, ReflectionMember $member): iterable
    {
        $argumentType = $member->getType()?->getName();

        if ($argumentType === InputInterface::class || $argumentType === RawInputInterface::class) {
            return [$input];
        }

        if ($argumentType === OutputInterface::class) {
            return [$this->output];
        }

        if ($argumentType === SymfonyStyle::class) {
            return [new SymfonyStyle(
                $input,
                $this->output,
                // optional argument introduced with Symfony Console 8.1
                // on commit https://github.com/symfony/console/commit/2b468472ec5d0e4acbe00f97e62f6cd552509894
                $this->application->getDispatcher())
            ];
        }

        if ($argumentType === Cursor::class) {
            return [new Cursor($this->output, $input)];
        }

        if ($argumentType === Application::class) {
            return [$this->application];
        }

        if ($argumentType === Command::class) {
            try {
                $command = $this->application->find($this->commandName);
            } catch (CommandNotFoundException) {
                $command = null;
            }
            return [$command];
        }

        return [];
    }
}
