<?php declare(strict_types=1);

namespace Labrador\AsyncEvent\Test\Unit\Internal;

use Amp\Future;
use Labrador\AsyncEvent\Event;
use Labrador\AsyncEvent\Internal\ListenerInvocationContext;
use Labrador\AsyncEvent\Internal\NotInvoked;
use Labrador\AsyncEvent\ListenerRemovableBasedOnHandleCount;
use Labrador\AsyncEvent\Listener;
use Labrador\AsyncEvent\ListenerRegistration;
use Labrador\CompositeFuture\CompositeFuture;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ListenerInvocationContext::class)]
#[CoversClass(NotInvoked::class)]
final class ListenerInvocationContextTest extends MockeryTestCase {

    private Listener&MockInterface $listener;

    private Listener&ListenerRemovableBasedOnHandleCount&MockInterface $listenerWithLimits;

    private ListenerRegistration&MockInterface $registration;

    protected function setUp() : void {
        $this->listener = Mockery::mock(Listener::class);
        $this->listenerWithLimits = Mockery::mock(Listener::class, ListenerRemovableBasedOnHandleCount::class);
        $this->registration = Mockery::mock(ListenerRegistration::class);
    }

    public function testHandleRegisteredEventWithListenerReturnsFutureResultsInCompositeFutureThatCompletedValue() : void {
        $subject = new ListenerInvocationContext($this->listener, $this->registration, 'foo');

        $event = Mockery::mock(Event::class);
        $event->shouldReceive('name')->withNoArgs()->andReturn('foo');
        $this->listener->shouldReceive('handle')->once()->with($event)->andReturn(Future::complete('from my test'));
        $this->registration->shouldReceive('remove')->never();

        $compositeFuture = $subject->handle($event);

        self::assertInstanceOf(CompositeFuture::class, $compositeFuture);

        [$errors, $values] = $compositeFuture->awaitAll();

        self::assertEmpty($errors);
        self::assertSame(['from my test'], $values);
    }

    public function testHandleRegisteredEventWithListenerReturnsNullValueResultsInCompositeFutureWithSingleCompletedFuture() : void {
        $subject = new ListenerInvocationContext($this->listener, $this->registration, 'foo');

        $event = Mockery::mock(Event::class);
        $event->shouldReceive('name')->withNoArgs()->andReturn('foo');
        $this->listener->shouldReceive('handle')->once()->with($event)->andReturnNull();
        $this->registration->shouldReceive('remove')->never();

        [$errors, $values] = $subject->handle($event)->awaitAll();

        self::assertEmpty($errors);
        self::assertSame([null], $values);
    }

    public function testHandleWithEventRegisteredListenerReturnsCompositeFuture() : void {
        $subject = new ListenerInvocationContext($this->listener, $this->registration, 'foo');

        $event = Mockery::mock(Event::class);
        $event->shouldReceive('name')->withNoArgs()->andReturn('foo');
        $this->listener->shouldReceive('handle')->once()->with($event)->andReturn($expected = new CompositeFuture([Future::complete()]));
        $this->registration->shouldReceive('remove')->never();

        $actual = $subject->handle($event);

        self::assertSame($expected, $actual);
    }

    public function testHandleWithListenerHasReachedInvocationLimitCallsListenerRegistrationRemove() : void {
        $subject = new ListenerInvocationContext($this->listenerWithLimits, $this->registration, 'foobar');

        $event = Mockery::mock(Event::class);
        $event->shouldReceive('name')->withNoArgs()->andReturn('foobar');
        $this->listenerWithLimits->shouldReceive('handle')->once()->with($event)->andReturnNull();
        $this->listenerWithLimits->shouldReceive('handleLimit')->twice()->withNoArgs()->andReturn(1);
        $this->registration->shouldReceive('remove')->once()->withNoArgs()->andReturnNull();

        $actual1 = $subject->handle($event);
        self::assertInstanceOf(CompositeFuture::class, $actual1);

        $actual2 = $subject->handle($event);
        self::assertSame(NotInvoked::create(), $actual2);
    }

    public function testHandleWithEventNotRegisteredDoesNotInvokeListenerAndReturnsNotInvokedInstance() : void {
        $subject = new ListenerInvocationContext($this->listener, $this->registration, 'foo');

        $event = Mockery::mock(Event::class);
        $event->shouldReceive('name')->withNoArgs()->andReturn('bar');
        $this->listener->shouldReceive('handle')->never();
        $this->registration->shouldReceive('remove')->never();

        $actual = $subject->handle($event);

        self::assertSame(NotInvoked::create(), $actual);
    }

}
