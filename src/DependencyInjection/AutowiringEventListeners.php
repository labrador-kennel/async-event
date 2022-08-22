<?php

namespace Cspray\Labrador\AsyncEvent\DependencyInjection;

use Cspray\AnnotatedContainer\AnnotatedContainer;
use Cspray\AnnotatedContainer\Bootstrap\Observer;
use Cspray\AnnotatedContainer\ContainerDefinition;
use Cspray\Labrador\AsyncEvent\EventEmitter;
use Cspray\Labrador\AsyncEvent\Listener;
use Cspray\Labrador\AsyncEvent\OneTimeListener;

final class AutowiringEventListeners implements Observer {

    public function beforeCompilation() : void {
        // noop
    }

    public function afterCompilation(ContainerDefinition $containerDefinition) : void {
        // noop
    }

    public function beforeContainerCreation(ContainerDefinition $containerDefinition) : void {
        // noop
    }

    public function afterContainerCreation(
        ContainerDefinition $containerDefinition,
        AnnotatedContainer $container
    ) : void {
        $emitter = $container->get(EventEmitter::class);
        assert($emitter instanceof EventEmitter);
        foreach ($containerDefinition->getServiceDefinitions() as $serviceDefinition) {
            if ($serviceDefinition->isAbstract() ||
                !is_a($serviceDefinition->getType()->getName(), Listener::class, true)) {
                continue;
            }

            $listener = $container->get($serviceDefinition->getType()->getName());
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
