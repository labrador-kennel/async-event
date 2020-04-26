<?php declare(strict_types = 1);

namespace Cspray\Labrador\AsyncEvent;

use Amp\Promise;
use function Amp\call;

/**
 * An EventEmitter implementation powered by Amp.
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

    /**
     * @inheritDoc
     */
    public function on(string $event, callable $listener, array $listenerData = []) : string {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }
        $listenerId = base64_encode($event . ':' . bin2hex(random_bytes(8)));
        $this->listeners[$event][$listenerId] = [$listener, $listenerData];

        return $listenerId;
    }

    /**
     * @inheritDoc
     */
    public function off(string $listenerId) : void {
        $decodedListenerId = base64_decode($listenerId);
        // if the ':' is in the 0 position it is still invalid as an event name cannot be blank so this is intentional
        if (!$decodedListenerId || !strpos($decodedListenerId, ':')) {
            return;
        }

        $event = explode(':', $decodedListenerId)[0];

        if (isset($this->listeners[$event]) && isset($this->listeners[$event][$listenerId])) {
            unset($this->listeners[$event][$listenerId]);
        }
    }

    /**
     * @inheritDoc
     */
    public function once(string $event, callable $listener, array $listenerData = []) : string {
        $callback = function($event, $listenerData) use($listener) {
            $listenerId = $listenerData['__labrador_kennel_id'];
            $this->off($listenerId);
            return call($listener, $event, $listenerData);
        };
        $callback = $callback->bindTo($this, $this);
        return $this->on($event, $callback, $listenerData);
    }

    /**
     * @inheritDoc
     */
    public function emit(Event $event, PromiseCombinator $promiseCombinator = null) : Promise {
        $listeners = $this->listeners[$event->getName()] ?? [];
        $promises = [];
        foreach ($listeners as $listenerId => list($callable, $listenerData)) {
            $listenerData['__labrador_kennel_id'] = $listenerId;
            $promises[] = call($callable, $event, $listenerData);
        }
        $promiseCombinator = $promiseCombinator ?? $this->getDefaultPromiseCombinator();
        return $promiseCombinator->combine(...$promises);
    }

    /**
     * @inheritDoc
     */
    public function listenerCount(string $event) : int {
        return count($this->listeners($event));
    }

    /**
     * @inheritDoc
     */
    public function listeners(string $event) : array {
        return $this->listeners[$event] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function getDefaultPromiseCombinator() : PromiseCombinator {
        return $this->defaultCombinator ?? PromiseCombinator::All();
    }

    /**
     * @inheritDoc
     */
    public function setDefaultPromiseCombinator(PromiseCombinator $promiseCombinator) : void {
        $this->defaultCombinator = $promiseCombinator;
    }
}
