<?php declare(strict_types=1);

namespace Labrador\AsyncEvent\Test\Unit;

use Amp\CompositeException;
use Amp\DeferredFuture;
use Amp\Future;
use Amp\PHPUnit\AsyncTestCase;
use Labrador\AsyncEvent\AmpEventEmitter;
use Labrador\AsyncEvent\OneTimeListener;
use Labrador\AsyncEvent\StandardEvent;
use Labrador\AsyncEvent\Test\Unit\Stub\AddToValueStoreListener;
use Labrador\AsyncEvent\Test\Unit\Stub\StubEventListener;
use Labrador\AsyncEvent\Test\Unit\Stub\StubEventListenerProvider;
use Labrador\AsyncEvent\Test\Unit\Stub\ValueStore;
use Labrador\CompositeFuture\CompositeFuture;
use Revolt\EventLoop;

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

    public function testQueueing() : void {
        $valueStore = new ValueStore();
        $listener = new AddToValueStoreListener($valueStore, 'foo', 1);
        $subject = new AmpEventEmitter();
        $subject->register($listener);
        $subject->register(new OneTimeListener($listener));

        $subject->queue($this->standardEvent('foo'));

        // Force the event loop to tick again, otherwise our queued events won't run
        $deferred = new DeferredFuture();
        EventLoop::defer(static fn() => $deferred->complete());
        $deferred->getFuture()->await();

        self::assertSame([1, 1], $valueStore->getValues());
    }

    public function testQueueingFailure() : void {
        $listener = new StubEventListener('foo', Future::error($error = new \RuntimeException()));
        $subject = new AmpEventEmitter();
        $subject->register($listener);

        $subject->queue($this->standardEvent('foo'));

        $data = new \stdClass();
        $data->throwable = null;
        EventLoop::setErrorHandler(fn($throwable) => $data->throwable = $throwable);

        // Force the event loop to tick again, otherwise our queued events won't run
        $deferred = new DeferredFuture();
        EventLoop::defer(static fn() => $deferred->complete());
        $deferred->getFuture()->await();

        self::assertInstanceOf(CompositeException::class, $data->throwable);
        self::assertSame($data->throwable->getReasons(), [$error]);
    }

    public function testProviderGivenCallsCorrespondingListener() : void {
        $listenerProvider = new StubEventListenerProvider('something', null);

        $subject = new AmpEventEmitter();
        $subject->register($listenerProvider);
        $subject->emit($event = $this->standardEvent('something'));

        self::assertSame($event, $listenerProvider->getHandledEvent());
    }
}
