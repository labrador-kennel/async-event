<?php declare(strict_types=1);

namespace Cspray\Labrador\AsyncEvent;

use Amp\Future;
use Labrador\CompositeFuture\CompositeFuture;

final class OneTimeListener implements Listener {

    private ?ListenerRegistration $registration = null;

    public function __construct(private readonly Listener $listener) {}

    public function handle(Event $event) : Future|CompositeFuture|null {
        $handled = $this->listener->handle($event);
        $this->registration?->remove();
        return $handled;
    }

    public function setRegistration(ListenerRegistration $registration) : void {
        $this->registration = $registration;
    }

    public function canHandle(string $eventName) : bool {
        return $this->listener->canHandle($eventName);
    }
}