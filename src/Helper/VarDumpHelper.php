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

namespace Overtrue\PHPLint\Helper;

use Overtrue\PHPLint\Console\SectionEnum;

use Psr\Log\LoggerInterface;

use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

use function var_dump;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
final class VarDumpHelper implements HelperInterface
{
    private ?HelperSet $helperSet = null;

    private bool $nativePhpFunction = false;

    public function __construct(LoggerInterface $logger)
    {
        // @link https://symfony.com/doc/current/components/var_dumper.html
        $packageName = 'symfony/var-dumper';

        // @see https://getcomposer.org/doc/07-runtime.md#installed-versions
        if (!\Composer\InstalledVersions::isInstalled($packageName)) {
            $logger->warning(
                'Package "{packageName}" is not installed. Fallback to native PHP var_dump function.',
                [
                    '__section__' => SectionEnum::DEPENDENCY->label(),
                    'packageName' => $packageName
                ]
            );
            $this->nativePhpFunction = true;
        }
    }

    public function setHelperSet(?HelperSet $helperSet): void
    {
        $this->helperSet = $helperSet;
    }

    public function getHelperSet(): ?HelperSet
    {
        return $this->helperSet;
    }

    public function getName(): string
    {
        return 'var_dumper';
    }

    public function dump(mixed $variable): void
    {
        $var = $variable;

        if ($variable instanceof ConsoleErrorEvent) {
            $var = $variable->getError();
        }

        if ($this->nativePhpFunction) {
            var_dump($var);
            return;
        }

        $cloner = new VarCloner();
        $dumper = new CliDumper();
        $dumper->dump($cloner->cloneVar($var));
    }
}
