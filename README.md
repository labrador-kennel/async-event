# Labrador async-event

![Unit Testing & Code Lint](https://github.com/labrador-kennel/async-event/workflows/Unit%20Testing%20&%20Code%20Lint/badge.svg)
![Latest Release](https://img.shields.io/github/v/release/labrador-kennel/async-event)

Emit and listen to events within Amp's Loop allowing you to respond to things that happen within your application entirely 
within an asynchronous context. An event is an occurrence of something meaningful has occurred in your application 
that you'd like outside code to be aware of. Some important information about events as `async-events` understands them 
includes...

- The `Event` type represents these meaningful occurrences in your application and are passed to listeners upon emitting the event.
- All events have a target, represented by the `object` type, that specifies what the event was triggered on.
- Events can have an arbitrary set of metadata associated to them represented by an associative array.

## Installation

[Composer] is the only supported method for installing Labrador packages.

```
composer require cspray/labrador-async-event
```

## Quick Start

If you'd rather get started quickly without having to read a bunch of documentation the code below demonstrates how to 
quickly emit events. Otherwise, we recommend checking out the Documentation section below for more information.

```php
<?php

use Cspray\Labrador\AsyncEvent\AmpEventEmitter;
use Cspray\Labrador\AsyncEvent\Event;
use Cspray\Labrador\AsyncEvent\StandardEventFactory;
use Amp\Loop;

Loop::run(function() {
    $emitter = new AmpEventEmitter();
    $emitter->on('my-event', function(Event $event) {
        // Do something when the event is triggered.        
    });

    $eventFactory = new StandardEventFactory();
    $target = new stdClass();   // normally this would be an object detailing what the event was triggered on
    $event = $eventFactory->create('my-event', $target);

    yield $emitter->emit($event);
});
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
