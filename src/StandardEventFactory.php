<?php

declare(strict_types = 1);

/**
 * @license See LICENSE file in project root
 */

namespace Cspray\Labrador\AsyncEvent;

use Cspray\Labrador\Exception\InvalidTypeException;

class StandardEventFactory implements EventFactory {

    private $eventFactories = [];

    public function create(string $eventName, $target, array $eventData = [], ...$args) : Event {
        if (!isset($this->eventFactories[$eventName])) {
            return new StandardEvent($eventName, $target, $eventData);
        }

        $event = $this->eventFactories[$eventName]($target, $eventData, ...$args);
        $this->validateFactoryCreatedEvent($event, $eventName);

        return $event;
    }

    private function validateFactoryCreatedEvent($event, $eventName) {
        if (!$event instanceof Event) {
            $msg = 'Factory functions MUST return an instance of %s but "%s" returned "%s".';
            throw new InvalidTypeException(sprintf($msg, Event::class, $eventName, gettype($event)));
        }

        if ($event->name() !== $eventName) {
            $msg = 'Factory functions MUST return an instance of %s with the same name as "%s"';
            throw new InvalidTypeException(sprintf($msg, Event::class, $eventName));
        }
    }

    public function register(string $eventName, callable $factoryFunction) {
        $this->eventFactories[$eventName] = $factoryFunction;
    }
}
