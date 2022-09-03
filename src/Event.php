<?php declare(strict_types=1);

namespace Labrador\AsyncEvent;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Implementations that represent a semantic event triggered by Labrador or a Labrador powered Application.
 *
 * @package Labrador\AsyncEvent
 */
interface Event {

    /**
     * The name of the Event being invoked; this value is passed to Listener::canHandle to determine if the given
     * Listener should be invoked.
     *
     * @return string
     */
    public function getName() : string;

    /**
     * An object that represents the target of the event.
     *
     * In the context of Labrador events MUST be invoked either on or in context of an object. Generally the target
     * of the event should be apparent. If your targets are not intrinsically targeting an object it may be a sign that
     * your types are not fully designed.
     *
     * @return object
     */
    public function getTarget() : object;

    /**
     * An arbitrary set of data associated to this Event.
     *
     * Perhaps the target alone is not sufficient data to pass about this event or there is meta-data that you'd like
     * to associate with the event (e.g. what host triggered an event in a network-enabled emitter).
     *
     * @return array
     */
    public function getData() : array;

    /**
     * The time that the Event was triggered.
     *
     * @return DateTimeImmutable
     */
    public function getCreatedAt() : DateTimeImmutable;
}
