<?php declare(strict_types=1);

namespace Labrador\AsyncEvent;

use Amp\Future;
use Labrador\CompositeFuture\CompositeFuture;

final class OneTimeListener extends AbstractListener {

    public function __construct(private readonly Listener $listener) {
    }

    public function handle(Event $event) : Future|CompositeFuture|null {
        $handled = $this->listener->handle($event);
        $this->getRegistration()?->remove();
        return $handled;
    }

    public function canHandle(string $eventName) : bool {
        return $this->listener->canHandle($eventName);
    }
}
