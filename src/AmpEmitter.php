<?php declare(strict_types = 1);

namespace Labrador\AsyncEvent;

use Amp\CompositeException;
use Amp\DeferredFuture;
use Amp\Future;
use Closure;
use Labrador\AsyncEvent\Internal\ListenerInvocationContext;
use Labrador\AsyncEvent\Internal\NotInvoked;
use Labrador\CompositeFuture\CompositeFuture;
use Random\RandomException;
use Revolt\EventLoop;
use Throwable;

/**
 * @api
 */
final class AmpEmitter implements Emitter {

    /**
     * @var array<non-empty-string, ListenerInvocationContext>
     */
    private array $listenerInvocationContexts = [];

    /**
     * @param non-empty-string $eventName
     * @throws RandomException
     */
    public function register(string $eventName, Listener $listener) : ListenerRegistration {
        $listenerKey = random_bytes(16);
        $context = new ListenerInvocationContext(
            $listener,
            $registration = $this->listenerRegistration($listenerKey),
            $eventName
        );
        $this->listenerInvocationContexts[$listenerKey] = $context;
        return $registration;
    }

    private function listenerRegistration(string $listenerKey) : ListenerRegistration {
        return new class(function() use($listenerKey): void {
            unset($this->listenerInvocationContexts[$listenerKey]);
        }) implements ListenerRegistration {
            public function __construct(private readonly Closure $remover) {
            }

            public function remove() : void {
                ($this->remover)();
            }
        };
    }

    /**
     * @inheritDoc
     */
    public function emit(Event $event) : CompositeFuture {
        $future = new CompositeFuture([]);
        foreach ($this->listenerInvocationContexts as $listenerInvocationContext) {
            try {
                $compositeFutureOrNotInvoke = $listenerInvocationContext->handle($event);
                if ($compositeFutureOrNotInvoke instanceof NotInvoked) {
                    continue;
                }

                $future = $future->merge($compositeFutureOrNotInvoke);
            } catch (Throwable $throwable) {
                $future = $future->merge(new CompositeFuture([Future::error($throwable)]));
            }
        }
        return $future;
    }

    public function queue(Event $event) : FinishedNotifier {
        $deferred = new DeferredFuture();
        EventLoop::queue(function() use($event, $deferred) : void {
            /** @var list<Throwable> $exceptions */
            [$exceptions, $values] = $this->emit($event)->awaitAll();
            $exceptions === [] ? $deferred->complete($values) : $deferred->error(new CompositeException($exceptions));
        });
        return new class($deferred->getFuture()) implements FinishedNotifier {
            public function __construct(private readonly Future $future) {}

            public function finished(callable $callable) : void {
                $this->future->map(static fn(array $values) => $callable(null, $values))
                    ->catch(static function(Throwable $throwable) use($callable) {
                        // We know this is a CompositeException at this point because we are in control of the Future
                        // being passed in and the only call to `$deferred->error()` should be with a CompositeException
                        assert($throwable instanceof CompositeException);
                        $callable($throwable, []);
                    });
            }
        };
    }

    /**
     * @inheritDoc
     */
    public function listeners(string $event) : array {
        return array_values(
            array_map(
                static fn(ListenerInvocationContext $context) => $context->listener,
                array_filter(
                    $this->listenerInvocationContexts,
                    static fn(ListenerInvocationContext $listenerInvocationContext) => $listenerInvocationContext->isRegisteredEventName($event)
                )
            )
        );
    }
}
