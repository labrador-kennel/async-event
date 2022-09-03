<?php declare(strict_types=1);

namespace Labrador\AsyncEvent;

/**
 * Provides the capacity to remove a Listener.
 */
interface ListenerRegistration {

    public function remove() : void;
}
