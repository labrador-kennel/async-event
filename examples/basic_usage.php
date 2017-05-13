<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Cspray\Labrador\AsyncEvent\AmpEmitter;

$emitter = new AmpEmitter();

$emitter->on('foo', function() {
    echo 1;
    yield new \Amp\Delayed(0);
    echo 3;
});

$emitter->on('foo', function() {
    echo 2;
    yield new \Amp\Delayed(0);
    echo 4;
});

\Amp\Loop::run(function() use($emitter) {
    $eventTarget = new \stdClass();
    $emitter->emit(new \Cspray\Labrador\AsyncEvent\StandardEvent('foo', $eventTarget));
});
