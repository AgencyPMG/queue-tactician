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

namespace PMG\Queue\Tactician;

use League\Tactician\Middleware;
use PMG\Queue\Message;
use PMG\Queue\Producer;

/**
 * A tactician middleware that sends any commands that implement
 * `PMG\Queue\Message` to a queue backend.
 *
 * @since   1.0
 */
final class QueueingMiddleware implements Middleware
{
    /**
     * @var Producer
     */
    private $producer;

    public function __construct(Producer $producer)
    {
        $this->producer = $producer;
    }

    /**
     * {@inheritdoc}
     * @return mixed
     */
    public function execute($command, callable $next)
    {
        if ($command instanceof Message) {
            $this->producer->send($command);
            return;
        }

        if ($command instanceof QueuedCommand) {
            $command = $command->unwrap();
        }

        return $next($command);
    }
}
