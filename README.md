# Labrador Async Event

![Unit Testing & Code Lint](https://github.com/labrador-kennel/async-event/workflows/Unit%20Testing%20&%20Code%20Lint/badge.svg)
![Latest Release](https://img.shields.io/github/v/release/labrador-kennel/async-event)

Labrador Async Event provides a way to emit semantic events on the [amphp/amp](https://amphp.org) event loop. It provides a robust set 
of features for working with an event system, including:

- First-class representation of an event with the `Labrador\AsyncEvent\Event` interface.
- Events include a rich set of data; including the datetime the event was emitted and a type-safe, object payload.
- First-class representation of an event listener with the `Labrador\AsyncEvent\Listener` interface.
- Remove listeners using an object-oriented API.
- Emit fire & forget events that don't block current execution 

## Installation

[Composer](https://getcomposer.org) is the only supported method for installing Labrador packages.

```
composer require labrador-kennel/async-event
```

## Usage Guide

> This guide details how to use Async Event v4+. This version makes significant changes 
> and moves towards a type-safe, stable API. Please review the README in the tag for 
> the version you're using.

```php
<?php declare(strict_types=1);

namespace Labrador\AsyncEvent\Demo;

use Amp\Future;
use Labrador\AsyncEvent\AbstractEvent;
use Labrador\AsyncEvent\Event;
use Labrador\AsyncEvent\Listener;
use Labrador\CompositeFuture\CompositeFuture;

final class MyDomainObject {}

/**
 * @extends AbstractEvent<MyDomainObject>
 */
final class MyDomainEvent extends AbstractEvent {
    public function __construct(MyDomainObject $object) {
        parent::__construct('my-domain-event', $object);
    }
}

/**
 * @implements Listener<MyDomainObject>
 */
final class MyListener implements Listener {
    public function handle(Event $event) : Future|CompositeFuture|null {
        return null;
    }

}
```

Now, create an EventEmitter and register your Listener. Then emit an event!

```php
<?php declare(strict_types=1);

namespace Labrador\AsyncEvent\Demo;

use Amp\CompositeException;use Labrador\AsyncEvent\AmpEmitter;

$emitter = new AmpEmitter();

// You can remove the Listener later by calling $registration->remove()
$registration = $emitter->register('my-domain-event', new MyListener());
$myDomainObject = new MyDomainObject();

// Emit an event and call an await method on the CompositeFuture returned
$emitter->emit(new MyDomainEvent($myDomainObject))->await();

// Queue a fire & forget event, pass a callback to `finished()` if you want 
// to know when the listeners for queued event are finished
$emitter->queue(new MyDomainEvent($myDomainObject))
    ->finished(
        static fn(?CompositeException $exception, array $values) => doSomething()
    );
```
