<?php declare(strict_types = 1);

namespace Cspray\Labrador\AsyncEvent;

use Cspray\AnnotatedContainer\Attribute\Service;
use Labrador\CompositeFuture\CompositeFuture;

/**
 * Represents an object that allows listeners to respond to emitted events asynchronously.
 *
 * @package Cspray\Labrador\AsyncEvent
 */
#[Service]
interface EventEmitter {

    /**
     * @param Listener $listener
     * @return ListenerRegistration
     */
    public function register(Listener $listener) : ListenerRegistration;

    /**
     * Causes all registered Listener implementations that should handle $event->getName().
     *
     * Listeners will be executed in an async context in the order in which they are added to the emitter. If any
     * listener throws an exception a CompositeException will be thrown. The array of Throwables will have an index
     * that matches a listenerId returned from on() or once() with the corresponding value being the exception that it
     * threw.
     *
     * @param Event $event
     * @return CompositeFuture
     */
    public function emit(Event $event) : CompositeFuture;

    public function queue(Event $event) : void;

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
     * @return list<Listener>
     */
    public function getListeners(string $event) : array;
}
