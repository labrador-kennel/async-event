<?php

namespace Labrador\AsyncEvent\Test\Integration\Listeners;

use Amp\Future;
use Labrador\AsyncEvent\AbstractListener;
use Labrador\AsyncEvent\EventListener;
use Labrador\AsyncEvent\Event;
use Labrador\AsyncEvent\ListenerRemoval;
use Labrador\CompositeFuture\CompositeFuture;

#[EventListener(ListenerRemoval::AfterOneEvent)]
class BazListener extends AbstractListener {

    public function handle(Event $event) : Future|CompositeFuture|null {
        return Future::complete('baz');
    }

    public function canHandle(string $eventName) : bool {
        return $eventName === 'something';
    }
}
