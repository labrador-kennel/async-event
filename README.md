# Labrador Async Event

![Unit Testing & Code Lint](https://github.com/labrador-kennel/async-event/workflows/Unit%20Testing%20&%20Code%20Lint/badge.svg)
![Latest Release](https://img.shields.io/github/v/release/labrador-kennel/async-event)

Labrador Async Event provides a way to emit semantic events on the [amphp/amp](https://amphp.org) event loop. It provides a robust set of features for working with an event system, including:

- First-class representation of an event with the `Labrador\AsyncEvent\Event` interface.
- Events include a rich set of data; including the datetime the event was emitted, the target of the event, and a set of arbitrary metadata.
- First-class representation of an event listener with the `Labrador\AsyncEvent\Listener` interface.
- No more tracking arbitrary listener ids, remove listeners using an object-oriented API.

## Installation

[Composer](https://getcomposer.org) is the only supported method for installing Labrador packages.

```
composer require labrador-kennel/async-event
```

## Usage Guide

> This guide details how to use Async Event v3+. The upgrade from v2 to v3 represented a major backwards compatibility break. If you're using Async Event v2 it is recommended to stay on that version until a migration plan can be developed.

To get started with Async Event you'll need to implement one or more Listeners, register them to an EventEmitter, and then emit an Event. First, let's take a look at implementing a Listener.

```php
<?php declare(strict_types=1);

namespace Labrador\AsyncEvent\Demo;

use Amp\Future;
use Labrador\AsyncEvent\AbstractListener;
use Labrador\AsyncEvent\Event;
use Labrador\CompositeFuture\CompositeFuture;

final class MyListener extends AbstractListener {

    public function canHandle(string $eventName) : bool {
        return $eventName === 'my-app.event';
    }
    
    public function handle(Event $event) : Future|CompositeFuture|null {
        // do whatever you need to do when your handled event is emitted 
        return null;
    }

}
```

Now, create an EventEmitter and register your Listener. Then emit an event!

```php
<?php declare(strict_types=1);

namespace Labrador\AsyncEvent\Demo;

use Labrador\AsyncEvent\AmpEventEmitter;
use Labrador\AsyncEvent\StandardEvent;
use stdClass;

$emitter = new AmpEventEmitter();

// You can remove the Listener later by calling $registration->remove()
$registration = $emitter->register(new MyListener());

// You should replace this with your own semantic event target
$eventTarget = new stdClass();

// Emit an event, returns a CompositeFuture with which you can decide how to wait for Listener futures to resolve
$emitter->emit(new StandardEvent('my-app.event', $eventTarget))->await();

// Emit an event on the _next tick_ of the event loop
$emitter->queue(new StandardEvent('my-app.event', $eventTarget));
```
