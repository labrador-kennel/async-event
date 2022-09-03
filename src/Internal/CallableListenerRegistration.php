<?php

namespace Labrador\AsyncEvent\Internal;

use Labrador\AsyncEvent\ListenerRegistration;

final class CallableListenerRegistration implements ListenerRegistration {

    private $remover;

    public function __construct(callable $remover) {
        $this->remover = $remover;
    }

    public function remove() : void {
        ($this->remover)();
    }
}
