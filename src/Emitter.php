<?php declare(strict_types = 1);

namespace Labrador\AsyncEvent;

use Labrador\CompositeFuture\CompositeFuture;

/**
 * Represents an object that allows listeners to respond to emitted events asynchronously.
 *
 * @api
 */
interface Emitter {

    /**
     * Register a Listener to respond to emitted events; the ListenerRegistration returned can be used to remove the
     * Listener.
     *
     * @param non-empty-string $eventName
     * @param Listener $listener
     * @return ListenerRegistration
     */
    public function register(string $eventName, Listener $listener) : ListenerRegistration;

    /**
     * Immediately invokes all registered listeners that can handle the given $event.
     *
     * Listeners will be executed in the order in which they are added to the emitter. A Listener can return a Future
     * or a CompositeFuture if it requires async functionality. How exceptions triggered in the invoked Listeners are
     * handled will be determined on which method you call on CompositeFuture. Please see the Amp documentation on
     * Future awaiting functions for more details on how this is handled.
     *
     * It is important that the CompositeFuture returned has a method invoked that awaits completion! If you don't
     * explicitly call a method on the CompositeFuture the behavior for how Listeners will behave is undefined.
     *
     * @param Event $event
     * @return CompositeFuture
     */
    public function emit(Event $event) : CompositeFuture;

    /**
     * Schedule all registered listeners that can handle the given $event to be invoked on the next tick of the
     * event loop.
     *
     * On the next tick of the loop, the $event will be passed to Emitter::emit. The CompositeFuture that results
     * will be handled by calling awaitAll. The Future returned from this method will
     */
    public function queue(Event $event) : FinishedNotifier;

    /**
     * Returns a list of Listener implementations that can handle the provided event name.
     *
     * @param string $event
     * @return list<Listener>
     */
    public function listeners(string $event) : array;
}
