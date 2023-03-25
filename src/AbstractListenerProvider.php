<?php declare(strict_types=1);

namespace Labrador\AsyncEvent;

use Amp\Future;
use Closure;
use Labrador\CompositeFuture\CompositeFuture;

abstract class AbstractListenerProvider implements ListenerProvider {

    protected function __construct(
        private readonly array $handledEvents,
        private readonly Closure $closure
    ) {
    }

    final public function getListener() : Listener {
        return new class($this->handledEvents, $this->closure) extends AbstractListener {

            public function __construct(
                private readonly array $handledEvents,
                private readonly Closure $closure
            ) {
            }

            public function canHandle(string $eventName) : bool {
                return in_array($eventName, $this->handledEvents, true);
            }

            public function handle(Event $event) : Future|CompositeFuture|null {
                return ($this->closure)($event);
            }
        };
    }
}
