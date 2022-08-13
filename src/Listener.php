<?php declare(strict_types=1);

namespace Cspray\Labrador\AsyncEvent;

use Amp\Future;
use Cspray\AnnotatedContainer\Attribute\Service;
use Labrador\CompositeFuture\CompositeFuture;

#[Service]
interface Listener {

    public function setRegistration(ListenerRegistration $registration) : void;

    public function canHandle(string $eventName) : bool;

    public function handle(Event $event) : Future|CompositeFuture|null;

}