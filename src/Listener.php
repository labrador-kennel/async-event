<?php declare(strict_types=1);

namespace Labrador\AsyncEvent;

use Amp\Future;
use Labrador\CompositeFuture\CompositeFuture;

#[ListenerService]
interface Listener {

    /**
     * Called when the Listener is registered on an EventEmitter.
     *
     * Access to the ListenerRegistration allows a Listener the ability to remove itself if responding to every
     * handleable event is not necessary.
     *
     * @param ListenerRegistration $registration
     * @return void
     */
    public function setRegistration(ListenerRegistration $registration) : void;

    /**
     * Determines whether the Listener::handle method will be invoked with the emitted Event.
     *
     * @param string $eventName
     * @return bool
     */
    public function canHandle(string $eventName) : bool;

    /**
     * Perform whatever actions are appropriate for this Listener.
     *
     * You can return a Future or CompositeFuture to have it implicitly awaited or return null.
     *
     * @param Event $event
     * @return Future|CompositeFuture|null
     */
    public function handle(Event $event) : Future|CompositeFuture|null;
}
