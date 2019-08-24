<?php declare(strict_types = 1);

namespace Cspray\Labrador\AsyncEvent;

use Amp\Promise;

/**
 * Implementations represent
 *
 * @package Cspray\Labrador\AsyncEvent
 */
interface Emitter {

    public function on(string $event, callable $listener, array $listenerData = []) : string;

    public function off(string $listenerId);

    public function once(string $event, callable $listener, array $listenerData = []) : string;

    public function emit(Event $event, PromiseCombinator $promiseCombinator = null) : Promise;

    public function listenerCount(string $event) : int;

    public function listeners(string $event) : iterable;

    public function getDefaultPromiseCombinator() : PromiseCombinator;

    public function setDefaultPromiseCombinator(PromiseCombinator $promiseCombinator) : void;
}
