<?php declare(strict_types = 1);

namespace Cspray\Labrador\AsyncEvent;

use Amp\Promise;
use function Amp\call;

/**
 * An EventEmitter implementation powered by amphp.
 *
 * @package Cspray\Labrador\AsyncEvent
 * @license See LICENSE in source root
 */
final class AmpEventEmitter implements EventEmitter {

    private $listeners;
    private $defaultCombinator;

    public function __construct() {
        $this->listeners = [];
    }

    public function on(string $event, callable $listener, array $listenerData = []) : string {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }
        $listenerId = bin2hex(random_bytes(8));
        $this->listeners[$event][$listenerId] = [$listener, $listenerData];

        return base64_encode($event . ':' . $listenerId);
    }

    public function off(string $listenerId) : void {
        $decodedListenerId = base64_decode($listenerId);
        // if the ':' is in the 0 position it is still invalid as an event name cannot be blank so this is intentional
        if (!$decodedListenerId || !strpos($decodedListenerId, ':')) {
            return;
        }

        list($event, $listenerId) = explode(':', $decodedListenerId);

        if (isset($this->listeners[$event]) && isset($this->listeners[$event][$listenerId])) {
            unset($this->listeners[$event][$listenerId]);
        }
    }

    public function once(string $event, callable $listener, array $listenerData = []) : string {
        $callback = function($event, $listenerData) use($listener) {
            $listenerId = $listenerData['__labrador_kennel_id'];
            $this->off($listenerId);
            return call($listener, $event, $listenerData);
        };
        $callback = $callback->bindTo($this, $this);
        return $this->on($event, $callback, $listenerData);
    }

    public function emit(Event $event, PromiseCombinator $promiseCombinator = null) : Promise {
        $listeners = $this->listeners[$event->getName()] ?? [];
        $promises = [];
        foreach ($listeners as $listenerId => list($callable, $listenerData)) {
            $listenerData['__labrador_kennel_id'] = base64_encode($event->getName() . ':' . $listenerId);
            $promises[] = call($callable, $event, $listenerData);
        }
        $promiseCombinator = $promiseCombinator ?? $this->getDefaultPromiseCombinator();
        return $promiseCombinator->combine(...$promises);
    }

    public function listenerCount(string $event) : int {
        return count($this->listeners($event));
    }

    public function listeners(string $event) : array {
        $eventListeners = $this->listeners[$event] ?? [];
        $cleanListeners = [];
        foreach ($eventListeners as $listenerId => $listenerData) {
            $cleanListeners[base64_encode($event . ':' . $listenerId)] = $listenerData;
        }

        return $cleanListeners;
    }

    public function getDefaultPromiseCombinator() : PromiseCombinator {
        return $this->defaultCombinator ?? PromiseCombinator::All();
    }

    public function setDefaultPromiseCombinator(PromiseCombinator $promiseCombinator) : void {
        $this->defaultCombinator = $promiseCombinator;
    }
}
