<?php

namespace Labrador\AsyncEvent\Exception;

use Labrador\AsyncEvent\Event;

class InvalidEventFactory extends Exception {

    public static function fromFactoryDidNotReturnEvent(string $eventName, string $type) : self {
        $msg = 'Factory functions MUST return an instance of %s but "%s" returned "%s".';
        return new self(sprintf($msg, Event::class, $eventName, $type));
    }

    public static function fromFactoryReturnedIncorrectEventName(string $name) : self {
        $msg = 'Factory functions MUST return an instance of %s with the same name as "%s"';
        return new self(sprintf($msg, Event::class, $name));
    }
}
