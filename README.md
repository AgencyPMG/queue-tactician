# pmg/queue-tactician

This is a middleware for [Tactician](http://tactician.thephpleague.com/) to
integrate it with [pmg/queue](https://github.com/AgencyPMG/Queue).

## Installation and Usage

Install with composer.

```
composer require pmg/queue-tactician
```

To use it, add the middleware to your middleware chain sometime before the
default command handler middleware.

```php
use League\Tactician\CommandBus;
use League\Tactician\Handler\CommandHandlerMiddleware;
use PMG\Queue\Producer;
use PMG\Queue\Tactician\QueueingMiddleware;

/** @var Producer */
$producer = createAQueueProducerSomehow();

$bus = new CommandBus([
    new QueueingMiddleware($producer),
    new CommandHandlerMiddleware(/*...*/),
]);
```

## Enqueueing Commands

Any command that implements `PMG\Queue\Message` will be put into the queue via
the producer and no further middlewares will be called.

```php
use PMG\Queue\Message;

final class DoLongRunningStuff implements Message
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'LongRunningStuff';
    }
}

// goes right into the queue
$bus->handle(new DoLongRunningStuff());
```

## Dequeueing (Consuming) Commands


To use tactician to process the messages via the consumer, use
`PMG\Queue\Handler\TacticianHandler`.

```php
use PMG\Queue\DefaultConsumer;
use PMG\Queue\Handler\TacticianHandler;

/** @var League\Tactician\CommandBus $bus */
$handler = new TacticianHandler($bus);

/** @var PMG\Queue\Driver $driver */
$consumer = new DefaultConsumer($driver, $handler);

$consumer->run();
```

The above assumes that the `CommandBus` instance still has the
`QueueingMiddleware` installed. If not, you'll need to use your own handler that
invokes the command bus, perhaps via `CallableHandler`.

```php
use League\Tactician\CommandBus;
use League\Tactician\Handler\CommandHandlerMiddleware;
use PMG\Queue\DefaultConsumer;
use PMG\Queue\Message;
use PMG\Queue\Handler\CallableHandler;

// no QueueingMiddleware!
$differentBus = new CommandBus([
    new CommandHandlerMiddleware(/*...*/),
]);

$handler = new CallableHandler([$bus, 'handle']);

/** @var PMG\Queue\Driver $driver */
$consumer = new DefaultConsumer($driver, $handler);

$consumer->run();
```

## Beware of Wrapping This Handler with `PcntlForkingHandler`

The shared instance of the command bus means that it's very likely that things
like open database connections will cause issues if/when a child press is forked
to handle messages.

Instead, use the `CallableHandler` above and create a new command bus each time.
If your command bus has the `QueueingMiddleware` installed, you'll need to wrap
the incoming messages with `QueuedCommand` which prevents the message from going
right back in the queue.

```php
use League\Tactician\CommandBus;
use League\Tactician\Handler\CommandHandlerMiddleware;
use PMG\Queue\Message;
use PMG\Queue\Handler\CallableHandler;
use PMG\Queue\Tactician\QueuedCommand;
use PMG\Queue\Tactician\QueueingMiddleware;

function createCommandBus() {
    return new CommadnBus([
        new QueueingMiddleware(createAProduerSomehow()),
        new CommandHandlerMiddlware(/* ... */)
    ]);
}

$handler = new CallableHandler(function (Message $message) {
    $bus = createCommandBus();
    return $bus->handle(new QueuedCommand($message));
});

/** @var PMG\Queue\Driver $driver */
$consumer = new DefaultConsumer($driver, $handler);

$consumer->run();
```
