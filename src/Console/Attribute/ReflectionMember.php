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

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overtrue\PHPLint\Console\Attribute;

/**
 * Backport Code from https://github.com/symfony/console/blob/8.1/Attribute/Reflection/ReflectionMember.php
 *
 * @since Release 9.8.0
 */
class ReflectionMember extends \Symfony\Component\Console\Attribute\Reflection\ReflectionMember
{
    protected \ReflectionParameter|\ReflectionProperty $member;

    public function __construct(\ReflectionParameter|\ReflectionProperty $member)
    {
        parent::__construct($member);
        $this->member = $member;
    }

    public function getAttributes(string $class): array
    {
        return array_map(
            static fn (\ReflectionAttribute $attribute) => $attribute->newInstance(),
            $this->member->getAttributes($class, \ReflectionAttribute::IS_INSTANCEOF)
        );
    }

    public function isVariadic(): bool
    {
        return $this->member instanceof \ReflectionParameter && $this->member->isVariadic();
    }

    public function getMember(): \ReflectionParameter|\ReflectionProperty
    {
        return $this->member;
    }
}
