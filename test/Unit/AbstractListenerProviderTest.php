<?php declare(strict_types=1);

namespace Labrador\AsyncEvent\Test\Unit;

use Amp\Future;
use Labrador\AsyncEvent\Event;
use Labrador\AsyncEvent\Test\Unit\Stub\StubAbstractListenerProvider;
use PHPUnit\Framework\TestCase;

final class AbstractListenerProviderTest extends TestCase {

    public function testGetListenerCanHandleProvidedEvents() : void {
        $subject = new StubAbstractListenerProvider(
            ['foo', 'bar', 'baz'],
            static function() {
            }
        );
        $listener = $subject->getListener();

        self::assertTrue($listener->canHandle('bar'));
        self::assertFalse($listener->canHandle('qux'));
    }

    public function testGetListenerInvokesClosure() : void {
        $called = new \stdClass();
        $called->called = false;
        $subject = new StubAbstractListenerProvider(
            ['bar'],
            static function() use($called) {
                $called->called = true;
                return null;
            }
        );
        $listener = $subject->getListener();
        $listener->handle($this->getMockBuilder(Event::class)->getMock());

        self::assertTrue($called->called);
    }

    public function testGetListenerInvokesClosureAndReturnsCorrectValue() : void {
        $future = Future::complete();
        $subject = new StubAbstractListenerProvider(
            ['foo'],
            static fn() => $future
        );
        $listener = $subject->getListener();

        self::assertSame(
            $future,
            $listener->handle($this->getMockBuilder(Event::class)->getMock())
        );
    }
}
