<?php declare(strict_types=1);

namespace Cspray\Labrador\AsyncEvent;

use DateTimeImmutable;

/**
 * Implementations that represent a semantic event triggered by Labrador or a Labrador powered Application.
 *
 * @package Cspray\Labrador\AsyncEvent
 */
interface Event {

    /**
     * The name of the Event, listeners who register for this event must match this string to be invoked.
     *
     * @return string
     */
    public function name() : string;

    /**
     * An object that represents the target of the event.
     *
     * In the context of Labrador events MUST be invoked either on or in context of an object. Generally the target
     * of the event should be obvious. For example, the target of the Engine::START_UP_EVENT in Labrador\Core is the
     * Engine implementation. If your targets are not intrinsically targeting an object it may be a sign that your
     * types are not fully designed.
     *
     * @return object
     */
    public function target() : object;

    /**
     * An arbitrary set of data associated to this Event.
     *
     * Perhaps the target alone is not sufficient data to pass about this event or there is meta data that you'd like
     * to associate with the vent (e.g. what host triggered an event in a network-enabled emitter).
     *
     * It is important to remember that this is distinct and separate from the concept of $listenerData that is passed
     * to on() and once() on listener registration.
     *
     * @return array
     */
    public function data() : array;

    /**
     * The time that the Event was triggered.
     *
     * @return DateTimeImmutable
     */
    public function createdAt() : DateTimeImmutable;

}
