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

use League\Tactician\CommandBus;
use League\Tactician\Handler\CommandHandlerMiddleware;
use League\Tactician\Handler\CommandNameExtractor\ClassNameExtractor;
use League\Tactician\Handler\Locator\InMemoryLocator;
use League\Tactician\Handler\MethodNameInflector\HandleInflector;
use PMG\Queue\Producer;
use PMG\Queue\Tactician\Fixtures\DummyHandler;
use PMG\Queue\Tactician\Fixtures\NotMessage;
use PMG\Queue\Tactician\Fixtures\IsMessage;

class QueueingMiddlewareTest extends \PHPUnit_Framework_TestCase
{
    private $producer, $handler, $bus;

    public function testMessageCommandsAreAddedToTheQueueBackend()
    {
        $command = new IsMessage();
        $this->producer->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($command));

        $this->bus->handle($command);

        $this->assertNull($this->handler->command, 'should not have called the handler');
    }

    public function testMiddlwareUnwrapsQueuedCommandsAndSendsThemToTheNextMiddleware()
    {
        $command = new IsMessage();
        $this->producer->expects($this->never())
            ->method('send');

        $this->bus->handle(new QueuedCommand($command));

        $this->assertSame($command, $this->handler->command);
    }

    public function testMiddlewareIgnoreNonMessageOrQueuedCommands()
    {
        $command = new NotMessage();
        $this->producer->expects($this->never())
            ->method('send');

        $this->bus->handle($command);

        $this->assertSame($command, $this->handler->command);
    }

    protected function setUp()
    {
        $this->handler = new DummyHandler();
        $this->producer = $this->createMock(Producer::class);
        $this->bus = new CommandBus([
            new QueueingMiddleware($this->producer),
            new CommandHandlerMiddleware(
                new ClassNameExtractor(),
                new InMemoryLocator([
                    NotMessage::class       => $this->handler,
                    IsMessage::class        => $this->handler,
                ]),
                new HandleInflector()
            )
        ]);
    }
}
