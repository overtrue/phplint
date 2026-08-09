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

use Fidry\CpuCoreCounter\CpuCoreCounter;
use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Console\Attribute\ReflectionMember;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Input\InputInterface;

use function min;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
class JobValueResolver implements ValueResolverInterface
{
    public function __construct(
        protected ?int $defaultValue = null,
    ) {
        if (null === $this->defaultValue) {
            // Jobs auto-detection when "fidry/cpu-core-counter" package is installed
            $this->defaultValue = OptionDefinition::DEFAULT_JOBS;
            // @see https://getcomposer.org/doc/07-runtime.md#installed-versions
            if (\Composer\InstalledVersions::isInstalled('fidry/cpu-core-counter')) {
                $cpuDetector = new CpuCoreCounter();
                $this->defaultValue = $cpuDetector->getAvailableForParallelisation(1)->availableCpus;
            }
        }
    }

    public function resolve(string $argumentName, InputInterface $input, ReflectionMember $member): iterable
    {
        $argumentType = $member->getType()?->getName();

        if ($argumentType !== 'int') {
            return [];
        }

        $argumentAttributes = $member->getAttribute(Option::class);
        // retrieve the argument name defined by the #[Option(name:)] attribute, or fallback to PHP variable name
        $argumentName = $argumentAttributes?->name ? : $argumentName;

        $value = $input->hasOption($argumentName) ? $input->getOption($argumentName) : null;

        if (empty($value)) {
            $value = $this->defaultValue;
        } else {
            $value = min($value, $this->defaultValue);
        }

        return [$value];
    }
}
