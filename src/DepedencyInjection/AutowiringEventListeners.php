<?php

namespace Cspray\Labrador\AsyncEvent\DepedencyInjection;

use Cspray\AnnotatedContainer\ServiceGatheringListener;
use Cspray\Labrador\AsyncEvent\EventEmitter;
use Cspray\Labrador\AsyncEvent\Listener;
use Cspray\Labrador\AsyncEvent\OneTimeListener;
use function Cspray\Typiphy\objectType;

final class AutowiringEventListeners extends ServiceGatheringListener {

    protected function doServiceGathering() : void {
        $emitter = iterator_to_array($this->getServicesOfType(objectType(EventEmitter::class)))[0];
        assert($emitter instanceof EventEmitter);
        foreach ($this->getServicesOfType(objectType(Listener::class)) as $listener) {
            $reflection = new \ReflectionObject($listener);
            $autowiredAttr = $reflection->getAttributes(AutowiredListener::class);
            if (count($autowiredAttr) === 0) {
                continue;
            }

            $autowire = $autowiredAttr[0]->newInstance();
            assert($autowire instanceof AutowiredListener);

            if ($autowire->getListenerRemoval() === ListenerRemoval::AfterOneEvent) {
                $listener = new OneTimeListener($listener);
            }

            $emitter->register($listener);
        }
    }
}