<?php declare(strict_types = 1);

namespace Cspray\Labrador\AsyncEvent;

use Amp\Promise;
use Cspray\Labrador\AsyncEvent;

use function Amp\call;

class AmpEmitter implements AsyncEvent\Emitter {

    private $listeners = [];
    private $defaultCombinator;

    public function on(string $event, callable $listener, array $listenerData = []) : string {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }

        $internalId = bin2hex(random_bytes(8));
        $this->listeners[$event][$internalId] = [$listener, $listenerData];
        return $event . ':' . $internalId;
    }

    public function off(string $listenerId) {
        list($event, $id) = explode(':', $listenerId);
        if (isset($this->listeners[$event]) && isset($this->listeners[$event][$id])) {
            unset($this->listeners[$event][$id]);
        }
    }

    public function once(string $event, callable $listener, array $listenerData = []) : string {
        $callback = function($event, $listenerData) use($listener) {
            $listenerId = $listenerData['id'];
            $this->off($listenerId);
            return call($listener, $event, $listenerData);
        };
        $callback = $callback->bindTo($this, $this);
        return $this->on($event, $callback, $listenerData);
    }

    public function emit(Event $event, PromiseCombinator $promiseCombinator = null) : Promise {
        $promises = [];
        foreach ($this->listeners($event->name()) as $listenerId => list($listener, $listenerData)) {
            $listenerData = array_merge($listenerData, ['id' => $event->name() . ':' . $listenerId]);
            $promises[] = call($listener, $event, $listenerData);
        }

        $promiseCombinator = $promiseCombinator ?? $this->getDefaultPromiseCombinator();
        return $promiseCombinator->combine(...$promises);
    }

    public function listenerCount(string $event) : int {
        return isset($this->listeners[$event]) ? count($this->listeners[$event]) : 0;
    }

    public function listeners(string $event) : iterable {
        return isset($this->listeners[$event]) ? $this->listeners[$event] : [];
    }

    public function getDefaultPromiseCombinator() : PromiseCombinator {
        return $this->defaultCombinator ?? PromiseCombinator::Any();
    }

    public function setDefaultPromiseCombinator(PromiseCombinator $promiseCombinator) : void {
        $this->defaultCombinator = $promiseCombinator;
    }
}
