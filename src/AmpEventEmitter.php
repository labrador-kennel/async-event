<?php declare(strict_types = 1);

namespace Labrador\AsyncEvent;

use Amp\CompositeException;
use Amp\Future;
use Labrador\AsyncEvent\Internal\CallableListenerRegistration;
use Labrador\CompositeFuture\CompositeFuture;
use Revolt\EventLoop;
use Throwable;

/**
 * An EventEmitter implementation powered by Amp.
 *
 * @license See LICENSE in source root
 */
final class AmpEventEmitter implements EventEmitter {

    /**
     * @var array<string, Listener>
     */
    private array $listeners = [];

    public function register(Listener|ListenerProvider $listener) : ListenerRegistration {
        $listenerKey = random_bytes(16);
        $remover = function() use($listenerKey) : void {
            unset($this->listeners[$listenerKey]);
        };
        if ($listener instanceof ListenerProvider) {
            $listener = $listener->getListener();
        }
        $registration = new CallableListenerRegistration($remover);
        $listener->setRegistration($registration);
        $this->listeners[$listenerKey] = $listener;
        return $registration;
    }

    /**
     * @inheritDoc
     */
    public function emit(Event $event) : CompositeFuture {
        $futures = array_map(
            function(Listener $listener) use($event) : Future|CompositeFuture {
                $futureOrNull = $listener->handle($event);
                if ($futureOrNull === null) {
                    return Future::complete();
                } else {
                    return $futureOrNull;
                }
            },
            $this->getListeners($event->getName())
        );

        $compositeFuture = new CompositeFuture([]);
        $confirmedFutures = [];

        /** @var Future|CompositeFuture $future */
        foreach ($futures as $future) {
            if ($future instanceof Future) {
                $confirmedFutures[] = $future;
            } else {
                $compositeFuture = $compositeFuture->merge($future);
            }
        }

        return $compositeFuture->merge(new CompositeFuture($confirmedFutures));
    }

    public function queue(Event $event) : void {
        EventLoop::queue(function() use($event) : void {
            /** @var list<Throwable> $exceptions */
            [$exceptions] = $this->emit($event)->awaitAll();
            if (count($exceptions) !== 0) {
                throw new CompositeException($exceptions);
            }
        });
    }

    /**
     * @inheritDoc
     */
    public function listenerCount(string $event) : int {
        return count($this->getListeners($event));
    }

    /**
     * @inheritDoc
     */
    public function getListeners(string $event) : array {
        return array_values(array_filter($this->listeners, fn(Listener $listener) => $listener->canHandle($event)));
    }
}
