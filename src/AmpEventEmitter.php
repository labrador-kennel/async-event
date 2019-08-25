<?php declare(strict_types = 1);

namespace Cspray\Labrador\AsyncEvent;

use Cspray\Labrador\AsyncEvent;

use Amp\Promise;
use Ds\Map;
use Ds\Pair;

use Exception as PhpException;

use function Amp\call;

/**
 * An EventEmitter implementation powered by amphp.
 *
 * @package Cspray\Labrador\AsyncEvent
 * @license See LICENSE in source root
 */
class AmpEventEmitter implements AsyncEvent\EventEmitter {

    private $listeners;
    private $defaultCombinator;

    public function __construct() {
        $this->listeners = new Map();
    }

    public function on(string $event, callable $listener, array $listenerData = []) : ListenerId {
        $listenerId = $this->createListenerId($event);

        $this->listeners->put($listenerId, new Pair($listener, $listenerData));

        return $listenerId;
    }

    public function off(ListenerId $listenerId) : void {
        if ($this->listeners->hasKey($listenerId)) {
            $this->listeners->remove($listenerId);
        }
    }

    public function once(string $event, callable $listener, array $listenerData = []) : ListenerId {
        $callback = function($event, $listenerData) use($listener) {
            $listenerId = $listenerData['__labrador_kennel_id'];
            $this->off($listenerId);
            return call($listener, $event, $listenerData);
        };
        $callback = $callback->bindTo($this, $this);
        return $this->on($event, $callback, $listenerData);
    }

    public function emit(Event $event, PromiseCombinator $promiseCombinator = null) : Promise {
        $promises = [];
        $this->listeners($event->name())->map(function($listenerId, $listenerPair) use($event, &$promises) {
            $listenerData = array_merge($listenerPair->value, ['__labrador_kennel_id' => $listenerId]);
            $promises[] = call($listenerPair->key, $event, $listenerData);
        });

        $promiseCombinator = $promiseCombinator ?? $this->getDefaultPromiseCombinator();
        return $promiseCombinator->combine(...$promises);
    }

    public function listenerCount(string $event) : int {
        return count($this->listeners($event));
    }

    public function listeners(string $event) : Map {
        return $this->listeners->filter(function(ListenerId $listenerId) use($event) {
             return $listenerId->getEventName() === $event;
        });
    }

    public function getDefaultPromiseCombinator() : PromiseCombinator {
        return $this->defaultCombinator ?? PromiseCombinator::All();
    }

    public function setDefaultPromiseCombinator(PromiseCombinator $promiseCombinator) : void {
        $this->defaultCombinator = $promiseCombinator;
    }

    private function createListenerId(string $event) : ListenerId {
        return new class($event) implements ListenerId {

            private $event;
            private $id;

            public function __construct(string $event) {
                try {
                    $id = bin2hex(random_bytes(8));
                } catch (PhpException $exception) {
                    error_log('Error creating listener ID. Falling back to non-CSRNG.');
                    error_log($exception->getMessage());
                    $id = uniqid('labrador_async_event');
                } finally {
                    $this->event = $event;
                    $this->id = $id;
                }
            }

            public function getEventName() : string {
                return $this->event;
            }

            public function getListenerId() : string {
                return $this->id;
            }
        };
    }
}
