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

        $handler->handle($msg = new IsMessage());

        $this->assertSame($commandHandler->command, $msg);
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

        $handler->handle(new IsMessage());
    }
}
