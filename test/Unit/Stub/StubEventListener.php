<?php

namespace Labrador\AsyncEvent\Test\Unit\Stub;

use Amp\Future;
use Labrador\AsyncEvent\Event;
use Labrador\AsyncEvent\Listener;
use Labrador\AsyncEvent\ListenerRegistration;
use Labrador\CompositeFuture\CompositeFuture;

class StubEventListener implements Listener {

    private ?Event $event = null;
    private ?ListenerRegistration $listenerRegistration = null;

    public function __construct(
        private readonly string $expectedEventName,
        private readonly Future|CompositeFuture|null $returnValue
    ) {
    }

    public function canHandle(string $eventName) : bool {
        return $this->expectedEventName === $eventName;
    }

    public function handle(Event $event) : Future|CompositeFuture|null {
        $this->event = $event;
        return $this->returnValue;
    }

    public function getEvent() : ?Event {
        return $this->event;
    }

    public function setRegistration(ListenerRegistration $registration) : void {
        $this->listenerRegistration = $registration;
    }

    public function getRegistration() : ?ListenerRegistration {
        return $this->listenerRegistration;
    }
}
