<?php

namespace Labrador\AsyncEvent\DependencyInjection;

use Cspray\AnnotatedContainer\AnnotatedContainer;
use Cspray\AnnotatedContainer\Bootstrap\ServiceGatherer;
use Cspray\AnnotatedContainer\Bootstrap\ServiceWiringObserver;
use Labrador\AsyncEvent\EventListener;
use Labrador\AsyncEvent\EventEmitter;
use Labrador\AsyncEvent\Listener;
use Labrador\AsyncEvent\ListenerRemoval;
use Labrador\AsyncEvent\OneTimeListener;

final class AutowireObserver extends ServiceWiringObserver {

    protected function wireServices(AnnotatedContainer $container, ServiceGatherer $gatherer) : void {
        $emitter = $container->get(EventEmitter::class);
        assert($emitter instanceof EventEmitter);

        /** @var Listener $listener */
        foreach ($gatherer->getServicesForType(Listener::class) as $listenerAndDefinition) {
            $listener = $listenerAndDefinition->getService();
            $autowire = $listenerAndDefinition->getDefinition()->getAttribute();

            assert($listener instanceof Listener);

            if ($autowire instanceof EventListener && $autowire->getListenerRemoval() === ListenerRemoval::AfterOneEvent) {
                $listener = new OneTimeListener($listener);
            }

            $emitter->register($listener);
        }
    }
}
