<?php declare(strict_types=1);

namespace Labrador\AsyncEvent\Test\Unit;

use Amp\CompositeException;
use Amp\DeferredFuture;
use Amp\Future;
use Amp\PHPUnit\AsyncTestCase;
use Labrador\AsyncEvent\AmpEmitter;
use Labrador\AsyncEvent\Event;
use Labrador\AsyncEvent\ListenerRemovableBasedOnHandleCount;
use Labrador\AsyncEvent\Listener;
use Labrador\CompositeFuture\CompositeFuture;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Revolt\EventLoop;
use stdClass;

/**
 * @covers \Labrador\AsyncEvent\AmpEmitter
 */
final class AmpEventEmitterTest extends AsyncTestCase {

    use MockeryPHPUnitIntegration;

    protected function tearDown() : void {
        EventLoop::setErrorHandler(null);
    }

    public function testRegisteringEventListenerIncrementsListenerCount() {
        $subject = new AmpEmitter();

        $a = Mockery::mock(Listener::class);
        $b = Mockery::mock(Listener::class);

        $subject->register('something', $a);

        self::assertSame([$a], $subject->listeners('something'));

        $subject->register('something', $b);

        self::assertSame([$a, $b], $subject->listeners('something'));
    }

    public function testRemovingEventListenerDecrementsListenerCount() {
        $subject = new AmpEmitter();

        $a = Mockery::mock(Listener::class);
        $b = Mockery::mock(Listener::class);

        $registrationA = $subject->register('foo', $a);
        $registrationB = $subject->register('foo', $b);

        self::assertSame([$a, $b], $subject->listeners('foo'));

        $registrationA->remove();

        self::assertSame([$b], $subject->listeners('foo'));

        $registrationB->remove();

        self::assertSame([], $subject->listeners('foo'));
    }

    public function testReturnedListenersRespectHandleableEvents() : void {
        $subject = new AmpEmitter();

        $a = Mockery::mock(Listener::class);
        $b = Mockery::mock(Listener::class);

        $subject->register('foobar', $a);
        $subject->register('something', $b);

        self::assertEquals([$b], $subject->listeners('something'));
    }

    public function testListenerCountWithNoRegisteredListeners() {
        $subject = new AmpEmitter();

        self::assertSame([], $subject->listeners('something'));
    }

    public function testEmittingEventInvokesRegisteredListeners() : void {
        $subject = new AmpEmitter();

        $listener = Mockery::mock(Listener::class);
        $event = Mockery::mock(Event::class);
        $event->shouldReceive('name')->once()->withNoArgs()->andReturn('foobar');
        $listener->shouldReceive('handle')->once()->with($event)->andReturnNull();

        $subject->register('foobar', $listener);
        $subject->emit($event);
    }

    public function testEmittingEventRespectsHandleableListeners() : void {
        $subject = new AmpEmitter();

        $event = Mockery::mock(Event::class);
        $a = Mockery::mock(Listener::class);
        $b = Mockery::mock(Listener::class);

        $event->shouldReceive('name')->withNoArgs()->andReturn('something');
        $a->shouldReceive('handle')->once()->with($event)->andReturnNull();
        $b->shouldReceive('handle')->never();

        $subject->register('something', $a);
        $subject->register('foo', $b);

        $subject->emit($event);
    }

    public function testEmittingEventHandlesMultipleListeners() : void {
        $subject = new AmpEmitter();

        $event = Mockery::mock(Event::class);
        $event->shouldReceive('name')->withNoArgs()->andReturn('something');

        $a = Mockery::mock(Listener::class);
        $a->shouldReceive('handle')->once()->with($event)->andReturnNull();

        $b = Mockery::mock(Listener::class);
        $b->shouldReceive('handle')->once()->with($event)->andReturnNull();

        $c = Mockery::mock(Listener::class);
        $c->shouldReceive('handle')->never();

        $d = Mockery::mock(Listener::class);
        $d->shouldReceive('handle')->once()->with($event)->andReturnNull();

        $subject->register('something', $a);
        $subject->register('something', $b);
        $subject->register('foo', $c);
        $subject->register('something', $d);

        $subject->emit($event);
    }

    public function testRunningListenerOnce() {
        $subject = new AmpEmitter();

        $event = Mockery::mock(Event::class);
        $event->shouldReceive('name')->withNoArgs()->andReturn('foobar');
        $listener = Mockery::mock(Listener::class, ListenerRemovableBasedOnHandleCount::class);
        $listener->shouldReceive('handle')->once()->with($event)->andReturnNull();
        $listener->shouldReceive('handleLimit')->twice()->withNoArgs()->andReturn(1);

        $subject->register('foobar', $listener);

        $subject->emit($event);
        $subject->emit($event);
    }

    public function testQueueing() : void {
        $event = Mockery::mock(Event::class);
        $event->shouldReceive('name')->withNoArgs()->andReturn('baz');

        $a = Mockery::mock(Listener::class);
        $a->shouldReceive('handle')->once()->with($event)->andReturn(null);

        $b = Mockery::mock(Listener::class);
        $b->shouldReceive('handle')->once()->with($event)->andReturn(Future::complete('future-value'));

        $c = Mockery::mock(Listener::class);
        $c->shouldReceive('handle')->once()->with($event)->andReturn(new CompositeFuture([
            Future::complete('composite-1'),
            Future::complete('composite-2'),
            Future::complete('composite-3')
        ]));

        $subject = new AmpEmitter();
        $subject->register('baz', $a);
        $subject->register('baz', $b);
        $subject->register('baz', $c);

        $data = new stdClass();
        $data->exception = null;
        $data->values = null;
        $subject->queue($event)->finished(function (?CompositeException $exception, array $values) use($data) {
            $data->exception = $exception;
            $data->values = $values;
        });

        $deferred = new DeferredFuture();
        EventLoop::defer(fn() => $deferred->complete());
        $deferred->getFuture()->await();

        self::assertNull($data->exception);
        self::assertSame([null, 'future-value', 'composite-1', 'composite-2', 'composite-3'], $data->values);
    }

    public function testQueueingFailure() : void {
        $subject = new AmpEmitter();

        $event = Mockery::mock(Event::class);
        $event->shouldReceive('name')->withNoArgs()->andReturn('added');

        $listener = Mockery::mock(Listener::class);
        $listener->shouldReceive('handle')
            ->once()
            ->with($event)
            ->andThrow($exception = new \RuntimeException('My exceptional circumstances'));

        $subject->register('added', $listener);

        $data = new stdClass();
        $data->exception = null;
        $data->values = null;
        $subject->queue($event)->finished(function (?CompositeException $exception, array $values) use($data) {
            $data->exception = $exception;
            $data->values = $values;
        });

        $deferred = new DeferredFuture();
        EventLoop::defer(fn() => $deferred->complete());
        $deferred->getFuture()->await();

        self::assertInstanceOf(CompositeException::class, $data->exception);
        self::assertSame([], $data->values);
        self::assertSame([$exception], $data->exception->getReasons());
    }
}
