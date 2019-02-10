<?php declare(strict_types = 1);

namespace Cspray\Labrador\AsyncEvent;

use function Amp\call;
use Amp\Promise;
use Cspray\Labrador\AsyncEvent;

class AmpEmitter implements AsyncEvent\Emitter {

    private $listeners = [];

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
            return $this->executeListener($event, $listener, $listenerData);
        };
        $callback = $callback->bindTo($this, $this);
        return $this->on($event, $callback, $listenerData);
    }

    public function emit(Event $event) : Promise {
        $promises = [];
        foreach ($this->listeners($event->name()) as $listenerId => list($listener, $listenerData)) {
            $listenerData = array_merge($listenerData, ['id' => $event->name() . ':' . $listenerId]);
            $promises[] = $this->executeListener($event, $listener, $listenerData);
        }

        return Promise\any($promises);
    }

    private function executeListener(AsyncEvent\Event $event, callable $listener, array $listenerData) : Promise {
        return call($listener, $event, $listenerData);
    }

    public function listenerCount(string $event) : int {
        return isset($this->listeners[$event]) ? count($this->listeners[$event]) : 0;
    }

    public function listeners(string $event) : iterable {
        return isset($this->listeners[$event]) ? $this->listeners[$event] : [];
    }
}
