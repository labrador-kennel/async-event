<?php

namespace Cspray\Labrador\AsyncEvent\Test\Unit\Stub;

use Amp\Future;
use Cspray\Labrador\AsyncEvent\Event;
use Cspray\Labrador\AsyncEvent\Listener;
use Cspray\Labrador\AsyncEvent\ListenerRegistration;
use Labrador\CompositeFuture\CompositeFuture;

class AddToValueStoreListener implements Listener {


    public function __construct(
        private readonly ValueStore $store,
        private readonly string $event,
        private readonly int $value
    ) {
    }

    public function setRegistration(ListenerRegistration $registration) : void {
    }

    public function canHandle(string $eventName) : bool {
        return $this->event === $eventName;
    }

    public function handle(Event $event) : Future|CompositeFuture|null {
        $this->store->add($this->value);
        return null;
    }
}
