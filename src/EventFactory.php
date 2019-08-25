<?php declare(strict_types = 1);

namespace Cspray\Labrador\AsyncEvent;

/**
 * An interface to abstract the creation of Event objects so that your application can replace all Events triggered by
 * Labrador with your own domain-specific type.
 *
 * The exact mechanism to specify the creation of an event is beyond the scope of this interface. Any custom event
 * code MUST be provided before you pass this implementation to services or objects that emit events.
 *
 * @license See LICENSE file in project root
 */
interface EventFactory {

    /**
     * The EventFactory should take steps to ensure that an Event object is created, even if there is not a specific
     * Event creation mechanism for the given $eventName.
     *
     * @param string $eventName
     * @param object $target
     * @param array $eventData
     * @param array $eventConstructorArgs
     * @return Event
     */
    public function create(string $eventName, $target, array $eventData = [], ...$eventConstructorArgs) : Event;
}
