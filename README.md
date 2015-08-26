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

On the other side of the coin, the commands that come out of the queue will
still implement message. Using them with the same command bus directly would
mean that they just go right back into the queue. To get around that, wrap
messages with a `QueuedCommand` in your handler.

```php
use PMG\Queue\DefaultConsumer;
use PMG\Queue\Message;
use PMG\Queue\Resolver\SimpleResolver;
use PMG\Queue\Resolver\SimpleExecutor;
use PMG\Queue\Tactician\QueuedCommand;

// $bus is the command bus we created above
$resolver = new SimpleResolver(function (Message $message) use ($bus) {
    // wrap up the message from the queue with `QueuedCommand`
    $bus->handle(new QueuedCommand($message));
});

/** @var PMG\Queue\Driver $driver */
$consumer = new DefaultConsumer($driver, new SimpleExecutor($resolver));

$consumer->run();
```

The other option is to use a completely separate command bus -- one that doesn't
have the `QueueingMiddleware`.

```php
use League\Tactician\CommandBus;
use League\Tactician\Handler\CommandHandlerMiddleware;
use PMG\Queue\DefaultConsumer;
use PMG\Queue\Message;
use PMG\Queue\Resolver\SimpleResolver;
use PMG\Queue\Resolver\SimpleExecutor;

// no QueueingMiddleware!
$differentBus = new CommandBus([
    new CommandHandlerMiddleware(/*...*/),
]);

$resolver = new SimpleResolver(function (Message $message) use ($differentBus) {
    // no need to wrap here
    $differentBus->handle($message);
});

/** @var PMG\Queue\Driver $driver */
$consumer = new DefaultConsumer($driver, new SimpleExecutor($resolver));

$consumer->run();
```

There's no message handlers in this library because
`PMG\Queue\Executor\ForkingExecutor` makes it difficult to ensure safety (what
needs to be restarted between commands).
