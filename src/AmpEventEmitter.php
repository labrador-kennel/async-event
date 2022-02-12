<?php declare(strict_types = 1);

namespace Cspray\Labrador\AsyncEvent;

use Amp\CompositeException;
use Throwable;
use function Amp\async;
use function Amp\Future\awaitAll;

/**
 * An EventEmitter implementation powered by Amp.
 *
 * @package Cspray\Labrador\AsyncEvent
 * @license See LICENSE in source root
 */
final class AmpEventEmitter implements EventEmitter {

    private array $listeners = [];

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
            async($listener, $event, $listenerData)->await();
        };
        $callback = $callback->bindTo($this, $this);
        return $this->on($event, $callback, $listenerData);
    }

    /**
     * @inheritDoc
     */
    public function emit(Event $event) : void {
        $listeners = $this->listeners($event->getName());
        $futures = [];
        foreach ($listeners as $listenerId => list($callable, $listenerData)) {
            $listenerData['__labrador_kennel_id'] = $listenerId;
            $futures[$listenerId] = async($callable, $event, $listenerData);
        }
        [$errors,] = awaitAll($futures);
        if (!empty($errors)) {
            throw new CompositeException($errors);
        }
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
}
