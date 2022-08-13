<?php

namespace Cspray\Labrador\AsyncEvent\DepedencyInjection;

use Cspray\AnnotatedContainer\Attribute\ServiceAttribute;
use Cspray\Labrador\AsyncEvent\ListenerRegistration;

#[\Attribute(\Attribute::TARGET_CLASS)]
class AutowiredListener implements ServiceAttribute {

    public function __construct(
        private readonly ListenerRemoval $listenerRemoval = ListenerRemoval::NeverRemove,
        private readonly array $profiles = [],
        private readonly ?string $name = null
    ) {}

    public function getListenerRemoval() : ListenerRemoval {
        return $this->listenerRemoval;
    }

    public function getProfiles() : array {
        return  $this->profiles;
    }

    public function isPrimary() : bool {
        return false;
    }

    public function getName() : ?string {
        return $this->name;
    }
}