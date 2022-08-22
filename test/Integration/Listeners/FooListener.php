<?php

namespace Cspray\Labrador\AsyncEvent\Test\Integration\Listeners;

use Amp\Future;
use Cspray\Labrador\AsyncEvent\AbstractListener;
use Cspray\Labrador\AsyncEvent\DependencyInjection\AutowiredListener;
use Cspray\Labrador\AsyncEvent\Event;
use Labrador\CompositeFuture\CompositeFuture;

#[AutowiredListener]
class FooListener extends AbstractListener {

    public function handle(Event $event) : Future|CompositeFuture|null {
        return Future::complete('foo');
    }

    public function canHandle(string $eventName) : bool {
        return $eventName === 'something';
    }
}
