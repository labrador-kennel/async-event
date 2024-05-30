<?php declare(strict_types=1);

namespace Labrador\AsyncEvent;

use Amp\Future;
use Labrador\CompositeFuture\CompositeFuture;

/**
 * @template Payload as object
 * @api
 */
interface Listener {
    /**
     * Perform whatever actions are appropriate for this Listener.
     *
     * You can return a Future or CompositeFuture to have it implicitly awaited or return null.
     *
     * @param Event<Payload> $event
     * @return Future|CompositeFuture|null
     */
    public function handle(Event $event) : Future|CompositeFuture|null;
}
