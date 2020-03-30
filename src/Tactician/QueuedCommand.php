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

/**
 * Used to wrapped incoming commands. This exists so commands don't go into an
 * endless loop of queueing where the consumer dequeues the a command that
 * implements `PMG\Queue\Message` from the backend and immediable puts it
 * back into the queue via `QueueingMiddleware`.
 *
 * @since   1.0
 */
final class QueuedCommand
{
    /**
     * The wrapped command.
     *
     * @var object
     */
    private $message;

    public function __construct(object $message)
    {
        $this->message = $message;
    }

    /**
     * Pull the message out of the queued command.
     */
    public function unwrap() : object
    {
        return $this->message;
    }
}
