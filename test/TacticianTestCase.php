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

namespace PMG\Queue;

use League\Tactician\Handler\CommandHandlerMiddleware;
use League\Tactician\Handler\CommandNameExtractor\ClassNameExtractor;
use League\Tactician\Handler\Locator\InMemoryLocator;
use League\Tactician\Handler\MethodNameInflector\HandleInflector;

abstract class TacticianTestCase extends \PHPUnit\Framework\TestCase
{
    protected static function createHandlerMiddleware(array $handlers)
    {
        return new CommandHandlerMiddleware(
            new ClassNameExtractor(),
            new InMemoryLocator($handlers),
            new HandleInflector()
        );
    }
}
