<?php
/*
 * This file is part of pmg/queue-tactician
 *
 * Copyright (c) PMG <https://www.pmg.com>
 *
 * For full copyright information see the LICENSE file distributed
 * with this source code.
 *
 * @license     http://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace PMG\Queue\Handler;

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use League\Tactician\CommandBus;
use PMG\Queue\Message;
use PMG\Queue\MessageHandler;
use PMG\Queue\Tactician\QueuedCommand;

/**
 * Like `TacticianHandler`, but creates a new `CommandBus` for each message
 * via callable.
 *
 * @since 3.0
 */
final class CreatingTacticianHandler implements MessageHandler
{
    /**
     * @var callable(): CommandBus
     */
    private $factory;

    /**
     * @param callable(): CommandBus $factory
     */
    public function __construct(callable $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(object $message, array $options=[]) : PromiseInterface
    {
        $promise = new Promise(function () use (&$promise, $message, $options) {
            $bus = call_user_func($this->factory, $options);
            if (!$bus instanceof CommandBus) {
                return $promise->reject(new \UnexpectedValueException(sprintf(
                    '%s expected its factory to return an instance of %s, got "%s"',
                    __CLASS__,
                    CommandBus::class,
                    is_object($bus) ? get_class($bus) : gettype($bus)
                )));;
            }

            $result = $bus->handle(new QueuedCommand($message));

            $promise->resolve($result ?: true);
        });

        return $promise;
    }
}
