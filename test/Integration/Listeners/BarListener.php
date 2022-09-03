<?php

namespace Labrador\AsyncEvent\Test\Integration\Listeners;

use Amp\Future;
use Labrador\AsyncEvent\AbstractListener;
use Labrador\AsyncEvent\EventListener;
use Labrador\AsyncEvent\Event;
use Labrador\CompositeFuture\CompositeFuture;

#[EventListener]
class BarListener extends AbstractListener {

    public function handle(Event $event) : Future|CompositeFuture|null {
        return Future::complete('bar');
    }

    public function canHandle(string $eventName) : bool {
        return $eventName === 'something';
    }
}
