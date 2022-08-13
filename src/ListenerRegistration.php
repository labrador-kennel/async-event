<?php declare(strict_types=1);

namespace Cspray\Labrador\AsyncEvent;

interface ListenerRegistration {

    public function remove() : void;

}