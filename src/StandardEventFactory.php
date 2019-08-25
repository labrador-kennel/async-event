<?php declare(strict_types = 1);

namespace Cspray\Labrador\AsyncEvent;

use Cspray\Labrador\Exception\InvalidTypeException;
use Ds\Map;

/**
 * An EventFactory implementation that will fallback to constructing a StandardEvent in the case where an Event is
 * created with a custom factory specified.
 *
 * If you do want to override the type of any Event created simply call the register(string, callable) method.
 *
 * @package Cspray\Labrador\AsyncEvent
 * @license See LICENSE in source root
 */
class StandardEventFactory implements EventFactory {

    private $eventFactories;

    public function __construct() {
        $this->eventFactories = new Map();
    }

    /**
     * @param string $eventName
     * @param object $target
     * @param array $eventData
     * @param mixed ...$args
     * @return Event
     * @throws InvalidTypeException
     */
    public function create(string $eventName, $target, array $eventData = [], ...$args) : Event {
        if (!$this->eventFactories->hasKey($eventName)) {
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

    /**
     * The $factoryFunction will be invoked whenever an event with the given $eventName is created.
     *
     * The method signature for $factoryFunction should match:
     *
     * function(object, array, ...args) {}
     *
     * The object is the target for the created Event.
     * The array is any data that should be associated to the Event.
     * The variadic args are any constructor arguments that the Event requires OR was simply passed to the create call.
     *
     * @param string $eventName
     * @param callable $factoryFunction
     */
    public function register(string $eventName, callable $factoryFunction) {
        $this->eventFactories->put($eventName, $factoryFunction);
    }
}
