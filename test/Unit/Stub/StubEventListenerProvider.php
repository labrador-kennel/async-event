<?php declare(strict_types=1);

namespace Labrador\AsyncEvent\Test\Unit\Stub;

use Amp\Future;
use Labrador\AsyncEvent\AbstractListener;
use Labrador\AsyncEvent\Event;
use Labrador\AsyncEvent\Listener;
use Labrador\AsyncEvent\ListenerProvider;
use Labrador\CompositeFuture\CompositeFuture;

class StubEventListenerProvider implements ListenerProvider {

    public readonly Listener $listener;

    public function __construct(
        private readonly string $expectedEventName,
        private readonly Future|CompositeFuture|null $returnValue
    ) {
        $this->listener = new class($this->expectedEventName, $this->returnValue) extends AbstractListener {

            private ?Event $event = null;

            public function __construct(
                private readonly string $eventName,
                private readonly Future|CompositeFuture|null $return,
            ) {
            }

            public function canHandle(string $eventName) : bool {
                return $this->eventName === $eventName;
            }

            public function handle(Event $event) : Future|CompositeFuture|null {
                $this->event = $event;
                return $this->return;
            }

            public function getHandledEvent() : ?Event {
                return $this->event;
            }
        };
    }

    public function getListener() : Listener {
        return $this->listener;
    }

    public function getHandledEvent() : ?Event {
        return $this->listener->getHandledEvent();
    }
}
