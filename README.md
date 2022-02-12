# Labrador async-event

![Unit Testing & Code Lint](https://github.com/labrador-kennel/async-event/workflows/Unit%20Testing%20&%20Code%20Lint/badge.svg)
![Latest Release](https://img.shields.io/github/v/release/labrador-kennel/async-event)

Easily emit semantic application events on an [Amp](https://amphp.org/) EventLoop! Provides an `EventEmitter` and opinionated constructs for  
registering listeners and executing them in an async context when the appropriate event is emitted. Some key concepts 
of the library include:

- An `Event` type represents meaningful occurrences in your application that might need to be responded to by other components or pieces of code.
- All events have a target, represented by the `object` type, that specifies what the event was triggered on.
- Events can have an arbitrary set of metadata associated to them represented by an associative array.
- Listeners can be registered on the emitter to be invoked with `Amp\async()` when the corresponding Event is emitted.

> This library is currently upgrading to Fiber support with PHP 8.1 and Amp v3.0. The underlying library is still in beta
> and, because of that, so is this one! However, we do not anticipate changes to the public facing API at this time.

## Installation

[Composer] is the only supported method for installing Labrador packages.

```
composer require cspray/labrador-async-event
```

## Quick Start

If you'd rather get started quickly without having to read a bunch of documentation the code below demonstrates how to 
quickly emit events and a demo of event listeners being handled asynchronously. Otherwise, we recommend checking out the 
Documentation section below for more information.

> This is a working example! Run `php demo.php` from the repository's root to see this example in action.

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Cspray\Labrador\AsyncEvent\AmpEventEmitter;
use Cspray\Labrador\AsyncEvent\Event;
use Cspray\Labrador\AsyncEvent\StandardEventFactory;
use function Amp\async;
use function Amp\delay;

async(function() {
    $emitter = new AmpEventEmitter();
    // Each listener is executed on its own fiber.
    $emitter->on('my-event', function(Event $event) {
        echo 'a';
        delay(0);
        echo 'd';
    });
    $emitter->on('my-event', function(Event $event) {
        echo 'b';
        delay(0);
        echo 'e';
    });
    $emitter->on('my-event', function(Event $event) {
        echo 'c';
    });

    $eventFactory = new StandardEventFactory();
    // You can call $eventFactory->register('my-event', fn() => new MyEvent()) to return your own types
    // Please see documentation for more details
    $target = new stdClass();   // normally this would be an object detailing what the event was triggered on
    $event = $eventFactory->create('my-event', $target);

    $emitter->emit($event)->await();
    // Will see 'abcde' in terminal
})->await();
?>
```

## Documentation

Labrador packages have thorough documentation in-repo in the `docs/` directory. You can also check out the documentation 
online at [https://labrador-kennel.io/docs/async-event](https://labrador-kennel.io/docs/async-event).

## Governance

All Labrador packages adhere to the rules laid out in the [Labrador Governance repo]

[amphp/amp]: https://amphp.org
[Composer]: https://getcomposer.org
[Labrador Governance repo]: https://github.com/labrador-kennel/governance
