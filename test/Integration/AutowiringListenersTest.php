<?php

namespace Cspray\Labrador\AsyncEvent\Test\Integration;

use Amp\PHPUnit\AsyncTestCase;
use Cspray\AnnotatedContainer\AnnotatedContainer;
use Cspray\AnnotatedContainer\ContainerDefinitionCompileOptionsBuilder;
use Cspray\Labrador\AsyncEvent\AmpEventEmitter;
use Cspray\Labrador\AsyncEvent\DepedencyInjection\AutowiringEventListeners;
use Cspray\Labrador\AsyncEvent\EventEmitter;
use Cspray\Labrador\AsyncEvent\StandardEvent;
use function Cspray\AnnotatedContainer\compiler;
use function Cspray\AnnotatedContainer\containerFactory;
use function Cspray\AnnotatedContainer\eventEmitter;

class AutowiringListenersTest extends AsyncTestCase {

    private static bool $listenerRegistered = false;

    private function getContainer() : AnnotatedContainer {
        if (!self::$listenerRegistered) {
            eventEmitter()->registerListener(new AutowiringEventListeners());
            self::$listenerRegistered = true;
        }
        $containerDef = compiler()->compile(
            ContainerDefinitionCompileOptionsBuilder::scanDirectories(
                __DIR__ . '/Listeners',                   // integration test source
                dirname(__DIR__, 2) . '/src'        // library's source
            )->build()
        );

        return containerFactory()->createContainer($containerDef);
    }

    public function testEmitOneTime() : void {
        $container = $this->getContainer();

        $emitter = $container->get(EventEmitter::class);

        self::assertInstanceOf(AmpEventEmitter::class, $emitter);
        self::assertCount(3, $emitter->getListeners('something'));
    }

    public function testOneTimeRemovalRespected() : void {
        $container = $this->getContainer();

        $emitter = $container->get(EventEmitter::class);

        self::assertInstanceOf(AmpEventEmitter::class, $emitter);

        $first = $emitter->emit(new StandardEvent('something', new \stdClass()))->await();
        sort($first);
        self::assertSame(['bar', 'baz', 'foo'], array_values($first));

        $second = $emitter->emit(new StandardEvent('something', new \stdClass()))->await();
        sort($second);
        self::assertSame(['bar', 'foo'], array_values($second));
    }
}