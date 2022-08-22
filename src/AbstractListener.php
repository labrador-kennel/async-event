<?php

namespace Cspray\Labrador\AsyncEvent;

abstract class AbstractListener implements Listener {

    private ?ListenerRegistration $registration = null;

    final public function setRegistration(ListenerRegistration $registration) : void {
        $this->registration = $registration;
    }

    final protected function getRegistration() : ?ListenerRegistration {
        return $this->registration;
    }

}
