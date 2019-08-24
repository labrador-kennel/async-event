<?php declare(strict_types = 1);

namespace Cspray\Labrador\AsyncEvent;

use Amp\Promise;
use Cspray\Labrador\AsyncEvent;

use Ds\Map;
use Ds\Pair;
use Ds\Vector;
use function Amp\call;

class AmpEmitter implements AsyncEvent\Emitter {

    private $listeners;
    private $defaultCombinator;

    public function __construct() {
        $this->listeners = new Map();
    }

    public function on(string $event, callable $listener, array $listenerData = []) : string {
        if (!$this->listeners->hasKey($event)) {
            $this->listeners->put($event, new Map());
        }

        /** @var Map $eventMap */
        $internalId = bin2hex(random_bytes(8));
        $this->listeners->get($event)->put($internalId, new Pair($listener, $listenerData));

        return $event . ':' . $internalId;
    }

    public function off(string $listenerId) {
        list($event, $id) = explode(':', $listenerId);
        if ($this->listeners->hasKey($event) && $this->listeners->get($event)->hasKey($id)) {
            $this->listeners->get($event)->remove($id);
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
        $this->listeners($event->name())->map(function($listenerId, $listenerPair) use($event, &$promises) {
            $listenerData = array_merge($listenerPair->value, ['id' => $event->name() . ':' . $listenerId]);
            $promises[] = call($listenerPair->key, $event, $listenerData);
        });

        $promiseCombinator = $promiseCombinator ?? $this->getDefaultPromiseCombinator();
        return $promiseCombinator->combine(...$promises);
    }

    public function listenerCount(string $event) : int {
        return count($this->listeners->get($event, []));
    }

    public function listeners(string $event) : Map {
        return $this->listeners->get($event, new Map());
    }

    public function getDefaultPromiseCombinator() : PromiseCombinator {
        return $this->defaultCombinator ?? PromiseCombinator::Any();
    }

    public function setDefaultPromiseCombinator(PromiseCombinator $promiseCombinator) : void {
        $this->defaultCombinator = $promiseCombinator;
    }
}
