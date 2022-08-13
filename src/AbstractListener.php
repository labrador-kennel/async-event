<?php

namespace Cspray\Labrador\AsyncEvent;

use Amp\Future;
use Labrador\CompositeFuture\CompositeFuture;

abstract class AbstractListener implements Listener {

    private ?ListenerRegistration $registration = null;

    public function __construct(
        private readonly string $handledEvent
    ) {}

    final public function setRegistration(ListenerRegistration $registration) : void {
        $this->registration = $registration;
    }

    final protected function getRegistration() : ?ListenerRegistration {
        return $this->registration;
    }

    final public function canHandle(string $eventName) : bool {
        return $this->handledEvent === $eventName;
    }

}