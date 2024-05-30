<?php declare(strict_types=1);

namespace Labrador\AsyncEvent;

use DateTimeImmutable;

/**
 * Implementations that represent a semantic event triggered by Labrador or a Labrador powered Application.
 *
 * @template Payload of object
 * @api
 */
interface Event {

    /**
     * The name of the Event being invoked; this value is passed to Listener::canHandle to determine if the given
     * Listener should be invoked.
     *
     * @return non-empty-string
     */
    public function name() : string;

    /**
     * An object that represents the target of the event.
     *
     * In the context of Labrador events MUST be invoked either on or in context of an object. Generally the target
     * of the event should be apparent. If your targets are not intrinsically targeting an object it may be a sign that
     * your types are not fully designed.
     *
     * @return Payload
     */
    public function payload() : object;

    /**
     * The time that the Event was triggered.
     *
     * @return DateTimeImmutable
     */
    public function triggeredAt() : DateTimeImmutable;
}
