<?php declare(strict_types = 1);

namespace Labrador\AsyncEvent;

use Labrador\AsyncEvent\Exception\InvalidEventFactory;

/**
 * An EventFactory implementation that will fallback to constructing a StandardEvent in the case where an Event is
 * created with a custom factory specified.
 *
 * If you do want to override the type of any Event created simply call the register(string, callable) method.
 *
 * @package Labrador\AsyncEvent
 * @license See LICENSE in source root
 */
final class StandardEventFactory implements EventFactory {

    /**
     * @var array<string, callable>
     */
    private array $eventFactories;

    public function __construct() {
        $this->eventFactories = [];
    }

    /**
     * @param string $eventName
     * @param object $target
     * @param array $eventData
     * @param mixed ...$args
     * @return Event
     * @throws InvalidEventFactory
     */
    public function create(string $eventName, object $target, array $eventData = [], ...$args) : Event {
        if (!array_key_exists($eventName, $this->eventFactories)) {
            return new StandardEvent($eventName, $target, $eventData);
        }

        $event = $this->eventFactories[$eventName]($target, $eventData, ...$args);
        $this->validateFactoryCreatedEvent($event, $eventName);

        assert($event instanceof Event);

        return $event;
    }

    private function validateFactoryCreatedEvent(mixed $event, string $eventName) : void {
        if (!$event instanceof Event) {
            throw InvalidEventFactory::fromFactoryDidNotReturnEvent(
                $eventName,
                is_object($event) ? $event::class : gettype($event)
            );
        }

        if ($event->getName() !== $eventName) {
            throw InvalidEventFactory::fromFactoryReturnedIncorrectEventName($eventName);
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
    public function register(string $eventName, callable $factoryFunction) : void {
        $this->eventFactories[$eventName] = $factoryFunction;
    }
}
