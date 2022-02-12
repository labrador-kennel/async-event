<?php declare(strict_types = 1);

namespace Cspray\Labrador\AsyncEvent;

use Amp\CompositeException;

/**
 * Represents an object that allows listeners to respond to emitted events asynchronously.
 *
 * @package Cspray\Labrador\AsyncEvent
 */
interface EventEmitter {

    /**
     * Invoke a listener every time an Event with a name matching $event is emitted; any data passed to $listenerData
     * will be passed to the $listener after the Event argument.
     *
     * Each $listener is invoked asynchronously. The resolved value is ignored, the $listener should never return a
     * value.
     *
     * The string returned is a unique listener ID that can be used to turn off an event listener with off(). The id
     * can also be used to determine which listener was responsible for an exception when emitting events.
     *
     * @param string $event
     * @param callable $listener function(Event $event, array $listenerData) : void
     * @param array $listenerData
     * @return string
     * @throws CompositeException
     */
    public function on(string $event, callable $listener, array $listenerData = []) : string;

    /**
     * Turns off a listener that is identified by $listenerId; this value should be one returned from on() or once().
     *
     * @param string $listenerId
     * @return void
     */
    public function off(string $listenerId) : void;

    /**
     * Ensures that a $listener is only executed one time the next time the event is emitted; after the event is
     * emitted this listener will be removed.
     *
     * The specific signature for an event $listener looks like:
     *
     * The returned string is an id that can identify this listener to turn off. Please note that unlike listeners
     * attached with on() there's a limited window for explicit removal of a once() listener.
     *
     * @param string $event
     * @param callable $listener function(Event $event, array $listenerData) : void
     * @param array $listenerData
     * @return string
     */
    public function once(string $event, callable $listener, array $listenerData = []) : string;

    /**
     * Causes all registered listeners for $event->name() to be invoked.
     *
     * Listeners will be executed in an async context in the order in which they are added to the emitter. If any
     * listener throws an exception a CompositeException will be thrown. The array of Throwables will have an index
     * that matches a listenerId returned from on() or once() with the corresponding value being the exception that it
     * threw.
     *
     * @param Event $event
     * @return void
     * @throws CompositeException
     */
    public function emit(Event $event) : void;

    /**
     * Return the number of listeners registered for a specific event.
     *
     * @param string $event
     * @return int
     */
    public function listenerCount(string $event) : int;

    /**
     * Returns a Map of lister information for a given $event.
     *
     * The key for the map will be the listener id return from on() or once() and the value for that key is a Pair that
     * represents the handler and any listenerData that was passed at time of listener registration.
     *
     * @param string $event
     * @return array<string, array<callable, array<mixed>>>
     */
    public function listeners(string $event) : array;
}
