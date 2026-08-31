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

use Overtrue\PHPLint\Configuration\Resolver\ArgumentResolverInterface;
use Overtrue\PHPLint\Console\Attribute\ReflectionMember;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function array_combine;

/**
 * Represents an invokable command.
 *
 * @author Yonel Ceruto <open@yceruto.dev> for Symfony Base Code
 * @author Laurent Laville for adaptee to internal command
 *
 * @since Release 9.8.0
 */
final class InvokableCommand extends \Symfony\Component\Console\Command\InvokableCommand
{
    protected readonly \ReflectionFunction $invokable;
    protected $code;

    protected ?array $parameters = null;

    private string $name;
    private string $description;

    public function __construct(
        protected readonly Command $command,
        callable $code,
        protected ArgumentResolverInterface $argumentResolver,
    ) {
        parent::__construct($command, $code);

        $this->invokable = new \ReflectionFunction($this->getClosure($code));

        $class = $this->invokable->getClosureScopeClass();
        $attribute = ($class->getAttributes(AsCommand::class)[0] ?? null)?->newInstance();

        $this->name = $attribute?->name ?? '';
        $this->description = $attribute?->description ?? '';
    }

    public function __invoke(InputInterface $input, OutputInterface $output): int
    {
        $statusCode = $this->invokable->invoke(...$this->getParameters($this->invokable, $input));

        if (!\is_int($statusCode)) {
            throw new \TypeError(\sprintf('The command "%s" must return an integer value in the "%s" method, but "%s" was returned.', $this->command->getName(), $this->invokable->getName(), get_debug_type($statusCode)));
        }

        return $statusCode;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getArguments(InputInterface $input): array
    {
        $parameters = $this->getParameters($this->invokable, $input);

        $function = $this->invokable;

        $parameterNames = [];
        foreach ($function->getParameters() as $index => $param) {
            $member = new ReflectionMember($param);

            $argumentAttributes = $member->getAttribute(Argument::class);

            if (null === $argumentAttributes) {
                $argumentAttributes = $member->getAttribute(Option::class);
            }
            $argumentName = $argumentAttributes?->name ? : $param->getName();

            $parameterNames[$index] = $argumentName;
        }

        // save list of all parameters and their names into a key-value map array
        return array_combine($parameterNames, $parameters);
    }

    protected function getParameters(\ReflectionFunction $function, InputInterface $input): array
    {
        if (null === $this->parameters) {
            $command = $this->invokable->getClosure();
            $this->parameters = $this->argumentResolver->getArguments($input, $command, $function);
        }
        return $this->parameters;
    }

    private function getClosure(callable $code): \Closure
    {
        if (!$code instanceof \Closure) {
            return $code(...);
        }

        if (null !== (new \ReflectionFunction($code))->getClosureThis()) {
            return $code;
        }

        set_error_handler(static function () {});
        try {
            if ($c = \Closure::bind($code, $this->command)) {
                $code = $c;
            }
        } finally {
            restore_error_handler();
        }

        return $code;
    }
}
