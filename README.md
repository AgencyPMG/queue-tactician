# pmg/queue-tactician

This middleware integrates
[Tactician](http://tactician.thephpleague.com/) with
[pmg/queue](https://github.com/AgencyPMG/Queue).

## Installation and Usage

Install with composer.

```
composer require pmg/queue-tactician
```

To use it, add the middleware to your middleware chain somewhere before the
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

Any command that implements `PMG\Queue\Message` will be sent to the queue via
the producer, and no further middleware will be called.

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

To use Tactician to process messages via the consumer, use
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

$handler = new CallableHandler([$differentBus, 'handle']);

/** @var PMG\Queue\Driver $driver */
$consumer = new DefaultConsumer($driver, $handler);

$consumer->run();
```

## Beware of Wrapping This Handler with `PcntlForkingHandler`

Because the command bus instance is shared, resources like open database
connections are likely to cause issues when a child process is forked to handle
messages.

Instead, create a new command bus for each message.
`CreatingTacticianHandler` can do that for you.

```php
use League\Tactician\CommandBus;
use League\Tactician\Handler\CommandHandlerMiddleware;
use PMG\Queue\Message;
use PMG\Queue\Handler\CallableHandler;
use PMG\Queue\Tactician\QueuedCommand;
use PMG\Queue\Tactician\QueueingMiddleware;
use PMG\Queue\Handler\CreatingTacticianHandler;

$handler = new CreatingTacticianHandler(function () {
    // This is invoked for every message.
    return new CommandBus([
        new QueueingMiddleware(createAProducerSomehow()),
        new CommandHandlerMiddleware(/* ... */)
    ]);
});

/** @var PMG\Queue\Driver $driver */
$consumer = new DefaultConsumer($driver, $handler);

$consumer->run();
```
