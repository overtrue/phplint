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

namespace Overtrue\PHPLint\Extension;

use Overtrue\PHPLint\Console\SectionEnum;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\Event;

use function debug_backtrace;
use function end;
use function get_debug_type;
use function json_encode;
use function sprintf;

use const DEBUG_BACKTRACE_IGNORE_ARGS;
use const JSON_UNESCAPED_SLASHES;

/**
 * @author Laurent Laville
 * @since Release 9.8.0
 */
abstract class AbstractManager implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Describes the contextual event and write a line to the current Logger instance (if available)
     *
     * @return array{
     *     command?: Command,
     *     arguments?: string[],
     *     eventType: string,
     *     listener: string,
     *     stopPropagation: string
     * }
     */
    protected function describeEvent(Event $event): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $trace = end($trace);

        $context = [
            'eventType' => get_debug_type($event),
            'listener' => sprintf('%s::%s', $trace['class'], $trace['function']),
            'stopPropagation' => $event->isPropagationStopped() ? 'stop' : 'continue',
        ];

        if ($event instanceof ConsoleEvent) {
            $context['command'] = $event->getCommand();
        }

        $message = sprintf(
            '<comment>%s</comment> %s',
            '"{eventType}" event {stopPropagation} propagation and was proceeded by following listener',
            ': {listener}'
        );

        if ($event instanceof GenericEvent) {
            $arguments = $event->getArguments();
            $valueResolvedDump = json_encode($arguments, JSON_UNESCAPED_SLASHES);
            $context['arguments'] = $valueResolvedDump;
            $message .= ' {arguments}';
        }

        $context['__section__'] = SectionEnum::EVENT->label();
        $context['__style__'] = SectionEnum::EVENT->value;

        $this->logger->debug($message, $context,);

        return $context;
    }

    protected function allowEvent(ConsoleEvent $event): bool
    {
        return $event->getCommand()?->getName() === 'lint';
    }
}
