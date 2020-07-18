# Handling Events

Triggering events with async-event is meant to be simple out-of-the-box but also to provide a tremendous amount of power 
and flexibility with how you can trigger and respond to events. Here we'll go over the basics for the most common use 
cases.

### Attaching Listeners

The first thing you'll need to do is setup listeners to respond to events that get triggered. This is accomplished 
through the `on()` and `once()` methods. We'll go over the `on()` API below, the `once()` API is similar with the only 
difference being that the listener will be removed after the first invocation.

```php
<?php declare(strict_types = 1);

require_once __DIR__ . '/vendor/autoload.php';

use Cspray\Labrador\AsyncEvent\AmpEventEmitter;
use Cspray\Labrador\AsyncEvent\Event;
use Cspray\Labrador\AsyncEvent\StandardEventFactory;
use Amp\Loop;

Loop::run(function() {
    $emitter = new AmpEventEmitter();
    $eventFactory = new StandardEventFactory();

    $emitter->on('foo', function(Event $event, array $listenerData) {
        echo 'Event name: ' . $event->getName(), PHP_EOL;
        echo 'Event date: ' . $event->getCreatedAt()->format('Y-m-d H:i:s'), PHP_EOL;
        echo 'Target: ', PHP_EOL, var_export($event->getTarget(), true), PHP_EOL;
        echo 'Event data: ', PHP_EOL, var_export($event->getData(), true), PHP_EOL;
        echo 'Listener data: ', PHP_EOL, var_export($listenerData, true), PHP_EOL;
    }, ['listener' => 'data']);

    // somewhere else in the codebase
    $target = new stdClass; // normally this would be some semantic object
    $target->data = 'target';
    $event = $eventFactory->create('foo', $target, ['event' => 'data']);
    yield $emitter->emit($event);
});
```

There isn't anything particularly fancy about our event handler, it just outputs the data and information available to 
us. It does demonstrate that a wealth of information is available and a couple concepts that are important to be aware
of.

- When events are triggered there is an object created that details the information about the event
- The Event object expects there to be a "target". A target is some semantic type or object that details _what_ the 
event was triggered on.

When ran the code above will output something similar to the following:

```
Event name: foo
Event date: 2020-04-26 13:53:06
Target: 
(object) array(
   'data' => 'target',
)
Event data: 
array (
  'event' => 'data',
)
Listener data: 
array (
  'listener' => 'data',
  '__labrador_kennel_id' => 'Zm9vOjNlYzc0OTkwNmRkMWE3YzQ=',
)
```

> Notice that this output includes a key in listener data that we did not provide. For more information about 
> this special key please check out ["Deep Dive: Listener IDs"](./references/listener-ids).

### Removing Listeners

Sometimes it may be desirable to remove a listener that has been attached. This functionality is provided by the `off()` 
comamnd. Calls to `on()` and `once()` return a unique string that is a listener ID; this listener ID is passed to 
`off()` to remove a listener.

```php
<?php declare(strict_types = 1);

require_once __DIR__ . '/vendor/autoload.php';

use Cspray\Labrador\AsyncEvent\AmpEventEmitter;
use Cspray\Labrador\AsyncEvent\StandardEventFactory;
use Amp\Loop;

Loop::run(function() {
    $emitter = new AmpEventEmitter();
    $eventFactory = new StandardEventFactory();
    $listenerId = $emitter->on('foo', function() {
        echo 'called it', PHP_EOL;
    });

    $triggerFooEvent = function() use($eventFactory, $emitter) {
        $event = $eventFactory->create('foo', new stdClass);
        return $emitter->emit($event);
    };
    
    yield $triggerFooEvent();
    yield $triggerFooEvent();
    yield $triggerFooEvent();
    
    $emitter->off($listenerId);
    
    yield $triggerFooEvent();
});
```

The expected output for this script would expect to see three lines of "called it" but not a fourth.

```
called it 
called it
called it
```

### Embracing Asynchronicity

A key component of this library is that event listeners are handled asynchronously. A prime example of this is that in 
the example above we yielded the `Promise` returned from `EventEmitter::emit()`. With the default `PromiseCombinator` 
this causes the calling method to only continue executing after all event listeners have resolved. If we didn't yield 
the returned Promise the calling method would continue executing before all event listeners have completely resolved. 
Understanding the differences with how to deal with the returned Promise and whether to yield it or "fire and forget" is 
a key aspect of using this library to its full advantage.