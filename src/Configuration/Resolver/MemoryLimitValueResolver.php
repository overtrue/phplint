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

use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Input\InputInterface;

use function ini_get;
use function preg_match;
use function strtolower;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
class MemoryLimitValueResolver implements ValueResolverInterface
{
    public function resolve(string $argumentName, InputInterface $input, ReflectionMember $member): iterable
    {
        $argumentType = $member->getType()?->getName(); // @phpstan-ignore method.notFound

        if ($argumentType !== 'int') {
            return [];
        }

        $argumentAttributes = $member->getAttribute(Option::class);
        // retrieve the argument name defined by the #[Option(name:)] attribute, or fallback to PHP variable name
        $argumentName = $argumentAttributes?->name ?: $argumentName;

        $value = $input->hasOption($argumentName) ? $input->getOption($argumentName) : null;

        if (null === $value) {
            $value = ini_get('memory_limit');
        }

        // @link https://www.php.net/manual/en/function.ini-get.php#126324
        preg_match('/([0-9]+)[\s]*([a-zA-Z]+)/', $value, $matches);

        $value = (isset($matches[1])) ? $matches[1] : 0;
        $metric = (isset($matches[2])) ? strtolower($matches[2]) : 'b';

        $value *= match ($metric) {
            'k', 'kb' => 1024,
            'm', 'mb' => (1024 ** 2),
            'g', 'gb' => (1024 ** 3),
            't', 'tb' => (1024 ** 4),
            default => 0
        };

        return [$value];
    }
}
