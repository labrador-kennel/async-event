<?php

require_once __DIR__ . '/vendor/autoload.php';

use Cspray\Labrador\AsyncEvent\AmpEventEmitter;
use Cspray\Labrador\AsyncEvent\Event;
use Cspray\Labrador\AsyncEvent\StandardEventFactory;
use function Amp\async;
use function Amp\delay;

async(function() {
    $emitter = new AmpEventEmitter();
    // Each listener is executed on its own fiber.
    $emitter->on('my-event', function(Event $event) {
        echo 'a';
        delay(0);
        echo 'd';
    });
    $emitter->on('my-event', function(Event $event) {
        echo 'b';
        delay(0);
        echo 'e';
    });
    $emitter->on('my-event', function(Event $event) {
        echo 'c';
    });

    $eventFactory = new StandardEventFactory();
    // You can call $eventFactory->register('my-event', fn() => new MyEvent()) to return your own types
    // Please see documentation for more details
    $target = new stdClass();   // normally this would be an object detailing what the event was triggered on
    $event = $eventFactory->create('my-event', $target);

    $emitter->emit($event)->await();
    // Will see 'abcde' in terminal
})->await();

echo PHP_EOL;
