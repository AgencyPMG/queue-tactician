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
 * A `MessageHandler` implementation backed by Tactician.
 *
 * @since 3.0
 */
final class TacticianHandler implements MessageHandler
{
    /**
     * @var CommandBus
     */
    private $tactician;

    public function __construct(CommandBus $tactician)
    {
        $this->tactician = $tactician;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Message $message, array $options=[]) : PromiseInterface
    {
        $promise = new Promise(function () use (&$promise, $message) {
            $promise->resolve($this->tactician->handle(new QueuedCommand(
                $message
            )));
        });

        return $promise;
    }
}
