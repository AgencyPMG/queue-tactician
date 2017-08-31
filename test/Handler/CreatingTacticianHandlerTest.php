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

class CreatingTacticianHandlerTest extends \PMG\Queue\TacticianTestCase
{
    private $commandHandler, $bus, $handler;

    public function testHandleCreatesACommandBusAndPassesOffTheMessage()
    {
        $commandHandler = new DummyHandler();
        $handler = new CreatingTacticianHandler(function () use ($commandHandler) {
            return new CommandBus([
                new QueueingMiddleware($this->createMock(Producer::class)),
                self::createHandlerMiddleware([
                    IsMessage::class => $commandHandler,
                ]),
            ]);
        });

        $promise = $handler->handle($msg = new IsMessage());
        $promise->wait();

        $this->assertSame($commandHandler->command, $msg);
    }

    public function testHandleResolveToTrueWhenTheHandlerDoesNotReturnATruthyValue()
    {
        $commandHandler = new DummyHandler();
        $commandHandler->returnValue = null;
        $handler = new CreatingTacticianHandler(function () use ($commandHandler) {
            return new CommandBus([
                new QueueingMiddleware($this->createMock(Producer::class)),
                self::createHandlerMiddleware([
                    IsMessage::class => $commandHandler,
                ]),
            ]);
        });

        $promise = $handler->handle(new IsMessage());
        $result = $promise->wait();

        $this->assertTrue($result);
    }

    public function testHandleResolveWithTheValueFromHandlerWhenTruthy()
    {
        $expected = new \stdClass();
        $commandHandler = new DummyHandler();
        $commandHandler->returnValue = $expected;
        $handler = new CreatingTacticianHandler(function () use ($commandHandler) {
            return new CommandBus([
                new QueueingMiddleware($this->createMock(Producer::class)),
                self::createHandlerMiddleware([
                    IsMessage::class => $commandHandler,
                ]),
            ]);
        });

        $promise = $handler->handle(new IsMessage());
        $result = $promise->wait();

        $this->assertSame($expected, $result);
    }

    public static function notCommandBuses()
    {
        return [
            [new \stdClass],
            [['an array']],
            ['a string'],
            [true],
            [null],
            [1],
            [2.0],
        ];
    }

    /**
     * @dataProvider notCommandBuses
     */
    public function testHandlerErrorsWhenTheFactoryDoesNotReturnTheCorrectType($bus)
    {
        $this->expectException(\UnexpectedValueException::class);

        $handler = new CreatingTacticianHandler(function () use ($bus) {
            return $bus;
        });

        $promise = $handler->handle(new IsMessage());
        $promise->wait();
    }
}
