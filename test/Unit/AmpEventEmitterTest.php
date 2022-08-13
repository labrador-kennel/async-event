<?php declare(strict_types=1);

namespace Cspray\Labrador\AsyncEvent\Test\Unit;

use Amp\Future;
use Amp\PHPUnit\AsyncTestCase;
use Cspray\Labrador\AsyncEvent\AmpEventEmitter;
use Cspray\Labrador\AsyncEvent\OneTimeListener;
use Cspray\Labrador\AsyncEvent\StandardEvent;
use Cspray\Labrador\AsyncEvent\Test\Stub\EvenNumberedDelayedListener;
use Cspray\Labrador\AsyncEvent\Test\Unit\Stub\AddToValueStoreListener;
use Cspray\Labrador\AsyncEvent\Test\Unit\Stub\StubEventListener;
use Cspray\Labrador\AsyncEvent\Test\Unit\Stub\ValueStore;
use Labrador\CompositeFuture\CompositeFuture;

class AmpEventEmitterTest extends AsyncTestCase {

    private function standardEvent(string $name, $target = null, array $eventData = []) {
        $target = $target ?? new \stdClass();
        return new StandardEvent($name, $target, $eventData);
    }

    public function testRegisteringEventListenerIncrementsListenerCount() {
        $subject = new AmpEventEmitter();
        $subject->register(new StubEventListener('something', Future::complete()));

        self::assertSame(1, $subject->listenerCount('something'));

        $subject->register(new StubEventListener('something', Future::complete()));

        self::assertSame(2, $subject->listenerCount('something'));
    }

    public function testRemovingEventListenerDecrementsListenerCount() {
        $subject = new AmpEventEmitter();
        $registrationOne = $subject->register(new StubEventListener('something', Future::complete()));
        $registrationTwo = $subject->register(new StubEventListener('something', Future::complete()));

        self::assertSame(2, $subject->listenerCount('something'));

        $registrationOne->remove();

        self::assertSame(1, $subject->listenerCount('something'));

        $registrationTwo->remove();

        self::assertSame(0, $subject->listenerCount('something'));
    }

    public function testRegisteringEventListenerIsReturnedInListeners() {
        $subject = new AmpEventEmitter();

        $subject->register($listener1 = new StubEventListener('something', Future::complete()));
        $subject->register($listener2 = new StubEventListener('something', Future::complete()));
        $expected = [$listener1, $listener2];

        $actual = $subject->getListeners('something');
        self::assertEquals($expected, $actual);
    }

    public function testReturnedListenersRespectHandleableEvents() : void {
        $subject = new AmpEventEmitter();

        $subject->register(new StubEventListener('foo', Future::complete()));
        $subject->register($listener2 = new StubEventListener('something', Future::complete()));
        $expected = [$listener2];

        $actual = $subject->getListeners('something');
        self::assertEquals($expected, $actual);
    }

    public function testListenerCountRespectHandleableEvents() : void {
        $subject = new AmpEventEmitter();

        $subject->register(new StubEventListener('foo', Future::complete()));
        $subject->register(new StubEventListener('something', Future::complete()));

        self::assertCount(1, $subject->getListeners('foo'));
    }

    public function testListenerCountWithNoRegisteredListeners() {
        $subject = new AmpEventEmitter();

        self::assertSame(0, $subject->listenerCount('something'));
    }

    public function testListenersWithNoRegisteredListeners() {
        $subject = new AmpEventEmitter();

        self::assertSame([], $subject->getListeners('something'));
    }

    public function testEmittingEvent() : void {
        $subject = new AmpEventEmitter();
        $subject->register($listener = new StubEventListener('foo', Future::complete()));

        $event = $this->standardEvent('foo');
        $subject->emit($event)->await();

        self::assertSame($event, $listener->getEvent());
    }

    public function testRegistrationAddedToListener() : void {
        $subject = new AmpEventEmitter();
        $registration = $subject->register(
            $listener = new StubEventListener('foo', Future::complete())
        );

        $subject->emit($this->standardEvent('foo'));

        self::assertSame($registration, $listener->getRegistration());
    }

    public function testEmittingEventRespectsHandleableListeners() : void {
        $subject = new AmpEventEmitter();

        $subject->register(new StubEventListener('foo', Future::complete('foo')));
        $subject->register(new StubEventListener('something', Future::complete('something')));

        $resolved = $subject->emit($this->standardEvent('something'))->await();

        self::assertSame(['something'], $resolved);
    }

    public function testEmittingEventHandlesMultipleListeners() : void {
        $subject = new AmpEventEmitter();

        $subject->register(new StubEventListener('something', Future::complete('foo')));
        $subject->register(new StubEventListener('something', Future::complete('something')));
        $subject->register(new StubEventListener('foo', Future::complete('bad')));
        $subject->register(new StubEventListener('something', Future::complete('bar')));

        $resolved = $subject->emit($this->standardEvent('something'))->await();

        self::assertSame(['foo', 'something', 'bar'], $resolved);
    }

    public function testEmittingEventHandlesNullReturnValue() : void {
        $subject = new AmpEventEmitter();

        $subject->register(new StubEventListener('bar', null));

        $resolved = $subject->emit($this->standardEvent('bar'))->await();

        self::assertSame([null], $resolved);
    }

    public function testEmittingAllPossibleReturnValues() : void {
        $subject = new AmpEventEmitter();

        $subject->register(new StubEventListener('something', Future::complete('foo')));
        $subject->register(
            new StubEventListener(
                'something',
                new CompositeFuture([Future::complete('baz'), Future::complete('qux')])
            )
        );
        $subject->register(new StubEventListener('foo', Future::complete('bad')));
        $subject->register(new StubEventListener('something', null));

        $resolved = $subject->emit($this->standardEvent('something'))->await();

        self::assertSame(['baz', 'qux', 'foo', null], $resolved);
    }

    public function testRunningListenerOnce() {
        $valueStore = new ValueStore();
        $listener = new AddToValueStoreListener($valueStore, 'foo', 1);
        $subject = new AmpEventEmitter();
        $subject->register(new OneTimeListener($listener));

        $subject->emit($this->standardEvent('foo'))->await();
        $subject->emit($this->standardEvent('foo'))->await();

        $this->assertSame([1], $valueStore->getValues());
    }
}
