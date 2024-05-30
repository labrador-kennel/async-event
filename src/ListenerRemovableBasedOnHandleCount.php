<?php declare(strict_types=1);

namespace Labrador\AsyncEvent;

/**
 * @api
 */
interface ListenerRemovableBasedOnHandleCount {

    public function handleLimit() : int;
}
