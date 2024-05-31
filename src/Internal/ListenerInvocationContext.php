<?php declare(strict_types=1);

namespace Labrador\AsyncEvent\Internal;

use Amp\Future;
use Labrador\AsyncEvent\Event;
use Labrador\AsyncEvent\EventName;
use Labrador\AsyncEvent\ListenerRemovableBasedOnHandleCount;
use Labrador\AsyncEvent\Listener;
use Labrador\AsyncEvent\ListenerRegistration;
use Labrador\CompositeFuture\CompositeFuture;

/**
 * @internal
 */
final class ListenerInvocationContext {

    private int $handledCount = 0;

    /**
     * @var non-empty-string
     */
    private readonly string $registeredEvent;

    /**
     * @template Payload of object
     * @param Listener<Payload> $listener
     * @param non-empty-string|EventName $registeredEvent
     */
    public function __construct(
        public readonly Listener             $listener,
        private readonly ListenerRegistration $registration,
        string|EventName $registeredEvent
    ) {
        $this->registeredEvent = $registeredEvent instanceof EventName ? $registeredEvent->name() : $registeredEvent;
    }

    public function handle(Event $event) : CompositeFuture|NotInvoked {
        if (!$this->isRegisteredEventName($event->name())) {
            return NotInvoked::create();
        }

        if ($this->isHandleLimitReached()) {
            $this->registration->remove();
            return NotInvoked::create();
        }

        $value = $this->listener->handle($event);
        $this->handledCount++;

        if ($value === null) {
            return new CompositeFuture([Future::complete()]);
        } elseif ($value instanceof Future) {
            return new CompositeFuture([$value]);
        }

        return $value;
    }

    /**
     * @param non-empty-string|EventName $eventName
     * @return bool
     */
    public function isRegisteredEventName(string|EventName $eventName) : bool {
        return ($eventName instanceof EventName ? $eventName->name() : $eventName) === $this->registeredEvent;
    }

    private function isHandleLimitReached() : bool {
        return $this->listener instanceof ListenerRemovableBasedOnHandleCount
            && $this->handledCount === $this->listener->handleLimit();
    }
}
