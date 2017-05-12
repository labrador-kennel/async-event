<?php declare(strict_types = 1);

/**
 * @license See LICENSE in project root
 */
namespace Cspray\Labrador\AsyncEvent;

use Amp\Promise;

interface Emitter {

    public function on(string $event, callable $listener, array $listenerData = []) : string;

    public function off(string $listenerId);

    public function once(string $event, callable $listener, array $listenerData = []) : string;

    public function emit(string $event, $target, array $eventData = []) : Promise;

    public function listenerCount(string $event) : int;

    public function listeners(string $event) : iterable;

}