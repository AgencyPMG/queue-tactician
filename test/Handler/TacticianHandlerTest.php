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

use League\Tactician\CommandBus;
use PMG\Queue\Producer;
use PMG\Queue\Tactician\QueuedCommand;
use PMG\Queue\Tactician\QueueingMiddleware;
use PMG\Queue\Fixtures\IsMessage;
use PMG\Queue\Fixtures\DummyHandler;

class TacticianHandlerTest extends \PMG\Queue\TacticianTestCase
{
    private $commandHandler, $bus, $handler;

    public function testHandleInvokesTheCommandBusWithAQueuedCommandThatsPasses()
    {
        $promise = $this->handler->handle($msg = new IsMessage());
        $promise->wait();

        $this->assertSame($this->commandHandler->command, $msg);
    }

    public function testHandleResolveToTrueWhenTheHandlerDoesNotReturnATruthyValue()
    {
        $this->commandHandler->returnValue = null;

        $promise = $this->handler->handle(new IsMessage());
        $result = $promise->wait();

        $this->assertTrue($result);
    }

    public function testHandleResolveWithTheValueFromHandlerWhenTruthy()
    {
        $expected = new \stdClass();
        $this->commandHandler->returnValue = $expected;

        $promise = $this->handler->handle(new IsMessage());
        $result = $promise->wait();

        $this->assertSame($expected, $result);
    }

    protected function setUp()
    {
        $this->commandHandler = new DummyHandler();
        $this->bus = new CommandBus([
            new QueueingMiddleware($this->createMock(Producer::class)),
            self::createHandlerMiddleware([
                IsMessage::class => $this->commandHandler,
            ]),
        ]);
        $this->handler = new TacticianHandler($this->bus);
    }
}
