<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Configuration\Resolver;

use function class_exists;

if (class_exists('\Symfony\Component\Console\ArgumentResolver\ValueResolver\ValueResolverInterface')) {
    // be ready to use with Symfony Console 8.1+ that include the new ArgumentResolver
    interface ValueResolverInterface extends \Symfony\Component\Console\ArgumentResolver\ValueResolver\ValueResolverInterface
    {
    }
} else {
    // be compatible with previous versions of Symfony Console that did not accept the ArgumentResolver
    interface ValueResolverInterface
    {
    }
}
