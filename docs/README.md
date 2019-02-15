# Labrador Kennel Async Event Documentation

This library is intended to help facilitate triggering and responding to an 
`Event` by utilizing the [Amp Event Loop] allowing your code to inform, and 
be informed, when important stuff happens without tight coupling. First, 
we'll take a look at the high-level concepts and then show off some examples 
for how Async Event might be used.

##  Event

An `Event` is an interface intended to describe what has happened in sufficient 
detail to allow listeners interested in the event to take reasonable actions on 
it. The Event interface looks like the following:

```php
<?php

namespace Cspray\Labrador\AsyncEvent;

interface Event {
    
    /**
     * The name of the event that should be triggered.
     *
     * To avoid possible collisions it is HIGHLY recommended that you prefix any
     * event names with the name of your vendor and/or package name. For example,
     * all Labrador Kennel events are prefixed with `labrador.repo` followed by 
     * the event name. 
     * 
     * @return string   
     */
    public function name() : string;

    /**
     * The target that this event was triggered on; typically we expect this to be
     * an object of some type that has relevance for your given domain.
     *
     * You SHOULD populate this value with something meaningful for your given domain.  
     * 
     * @return mixed
     */
    public function target();

    /**
     * A possible array of data that may be about the event or the target.
     * 
     * @return array
     */
    public function data() : array;

    /**
     * The time that this Event was created.
     * 
     * @return \DateTimeImmutable
     */
    public function createdAt() : \DateTimeImmutable;
}
```

This library provides a `StandardEvent` that has a simple constructors that 
accepts a `$name`, `$target`, and `$data` parameters that can be used if you 
do not want to implement your own custom event types. It is recommended that 
you provide your own custom types that are more descriptive of their intent.

### Implementing your own custom Event type

If you do decide to create your own custom Event AND also are utilizing the 
`StandardEventFactory` for event creation it is important that you register 
an appropriate factory function for your event. Please see the 
`StandardEventFactory::register` method for more details.

## Emitter

If an `Event` is the _what_ the `Emitter` is the _how_. Emitters allow you 
to manage listeners that will be invoked on Amp's Event Loop and to, of course, 
emit events. The Emitter interface looks like:

```php
<?php

namespace Cspray\Labrador\AsyncEvent;

use Amp\Promise;

interface Emitter {

    /**
     * Trigger the passed $listener whenever an event name matching $event is emitted.
     * 
     * If you pass any $listenerData it will be passed to the $listener AFTER the Event 
     * object. The returned ID should uniquely match this specific callable for this 
     * event. The ID should be able to be passed to `off()` to remove this listener.
     * 
     * @param string $event
     * @param callable $listener
     * @param array $listenerData
     * @return string
     */
    public function on(string $event, callable $listener, array $listenerData = []) : string;

    /**
     * Turns off any listener that might be reference by $listenerId. 
     * 
     * @param string $listenerId
     * @return mixed
     */
    public function off(string $listenerId);

    /**
     * Similar to `on()` except that the passed $listener will be removed the 
     * first it is called. 
     *  
     * @param string $event
     * @param callable $listener
     * @param array $listenerData
     * @return string
     */
    public function once(string $event, callable $listener, array $listenerData = []) : string;

    /**
     * Will invoke any listeners on an Event Loop that have been registered for the given $event.
     *
     * The returned Promise should resolve when ALL registered listeners have finished responding to the 
     * event. The Promise is highly failure resistant, meaning that even if every single listener throws 
     * an exception the returned Promise will still resolve succesfully.
     * 
     * @param Event $event
     * @return Promise
     */
    public function emit(Event $event) : Promise;
    
    /**
     * Return the number of listeners that have been registered for a given $event
     * 
     * @param string $event
     * @return int
     */
    public function listenerCount(string $event) : int;

    /**
     * Return the collection of listeners for a given $event.. 
     * @param string $event
     * @return iterable
     */
    public function listeners(string $event) : iterable;
}
```

The most important thing to note about emitting Events is that you can either ensure that all 
registered listeners are finished responding to your event by yielding the returned Promise 
before your code continues executing. Alternatively you can simply "fire and forget" the Event. 
Which should be used is up to you and what is most appropriate for the given use case.

[Amp Event Loop]: https://amphp.org/amp/event-loop/