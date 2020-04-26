<?php declare(strict_types=1);

namespace Cspray\Labrador\AsyncEvent\Test;

use Amp\Deferred;
use Amp\Delayed;
use Amp\Failure;
use Amp\Loop;
use Amp\PHPUnit\AsyncTestCase;
use Amp\Success;
use Cspray\Labrador\AsyncEvent\AmpEventEmitter;
use Cspray\Labrador\AsyncEvent\Event;
use Cspray\Labrador\AsyncEvent\PromiseCombinator;
use Cspray\Labrador\AsyncEvent\StandardEvent;

class AmpEventEmitterTest extends AsyncTestCase {

    private function standardEvent(string $name, $target = null, array $eventData = []) {
        $target = $target ?? new \stdClass();
        return new StandardEvent($name, $target, $eventData);
    }

    public function testDefaultPromiseCombinatorIsAll() {
        $subject = new AmpEventEmitter();

        $this->assertEquals(PromiseCombinator::All(), $subject->getDefaultPromiseCombinator());
    }

    public function testSettingDefaultPromiseCombinator() {
        $subject = new AmpEventEmitter();

        $subject->setDefaultPromiseCombinator(PromiseCombinator::Some());

        $this->assertEquals(PromiseCombinator::Some(), $subject->getDefaultPromiseCombinator());
    }

    public function testRegisteringEventListenerIncrementsListenerCount() {
        $subject = new AmpEventEmitter();
        $subject->on('something', function() {
        });

        $this->assertSame(1, $subject->listenerCount('something'));

        $subject->on('something', function() {
        });

        $this->assertSame(2, $subject->listenerCount('something'));
    }

    public function testRemovingEventListenerDecrementsListenerCount() {
        $subject = new AmpEventEmitter();
        $one = $subject->on('something', function() {
        });
        $two = $subject->on('something', function() {
        });

        $this->assertSame(2, $subject->listenerCount('something'));

        $subject->off($one);

        $this->assertSame(1, $subject->listenerCount('something'));

        $subject->off($two);

        $this->assertSame(0, $subject->listenerCount('something'));
    }

    public function testRegisteringEventListenerReturnsCorrectIdFormat() {
        $subject = new AmpEventEmitter();
        $id = $subject->on('something', function() {
        });

        $decodedId = base64_decode($id);
        $this->assertNotFalse($decodedId);
        $this->assertStringContainsString(':', $decodedId);

        list($event, $id) = explode(':', $decodedId);
        $this->assertSame('something', $event);
        $this->assertStringMatchesFormat('%x', $id);
    }

    public function testRegisteringEventListenerIsReturnedInListeners() {
        $subject = new AmpEventEmitter();
        $callbackOne = function() {
        };
        $callbackTwo = function() {
        };

        $idOne = $subject->on('something', $callbackOne);
        $idTwo = $subject->on('something', $callbackTwo);
        $expected = [];
        $expected[] = [$idOne, [$callbackOne, []]];
        $expected[] = [$idTwo, [$callbackTwo, []]];

        $actual = [];
        foreach ($subject->listeners('something') as $listenerId => $listenerAndData) {
            $actual[] = [$listenerId, $listenerAndData];
        }
        $this->assertEquals($expected, $actual);
    }

    public function testListenerCountWithNoRegisteredListeners() {
        $subject = new AmpEventEmitter();

        $this->assertSame(0, $subject->listenerCount('something'));
    }

    public function testListenersWithNoRegisteredListeners() {
        $subject = new AmpEventEmitter();

        $this->assertSame([], $subject->listeners('something'));
    }

    public function testEmittingEvent() {
        $data = new \stdClass();
        $data->data = [];
        $subject = new AmpEventEmitter();
        $subject->on('foo', function() use($data) {
            $data->data[] = 'foo';
        });

        yield $subject->emit($this->standardEvent('foo'));

        $this->assertSame(['foo'], $data->data);
    }

    public function testEmittingEventAsynchronously() {
        $data = new \stdClass();
        $data->data = [];
        $subject = new AmpEventEmitter();
        $subject->on('foo', function() use($data) {
            $data->data[] = 1;
            yield new Delayed(0);
            $data->data[] = 3;
            yield new Delayed(0);
            $data->data[] = 5;
        });

        $subject->on('foo', function() use($data) {
            $data->data[] = 2;
            yield new Delayed(0);
            $data->data[] = 4;
            yield new Delayed(0);
            $data->data[] = 6;
        });

        yield $subject->emit($this->standardEvent('foo'));

        $this->assertSame([1,2,3,4,5,6], $data->data);
    }

    public function testEmitPromiseResolves() {
        $data = new \stdClass();
        $data->data = [];
        $subject = new AmpEventEmitter();
        $subject->on('foo', function() use($data) {
            $data->data[] = 1;
        });

        $promise = $subject->emit($this->standardEvent('foo'));
        $promise->onResolve(function() use($data) {
            $data->data[] = 2;
        });
        yield new Delayed(0);

        $this->assertSame([1,2], $data->data);
    }

    public function testListenerReturnsPromise() {
        $data = new \stdClass();
        $data->data = [];
        $subject = new AmpEventEmitter();
        $subject->on('foobar', function() use($data) {
            $deferred = new Deferred();

            Loop::defer(function() use($data, $deferred) {
                $data->data[] = 1;
                $deferred->resolve();
            });

            return $deferred->promise();
        });

        $promise = $subject->emit($this->standardEvent('foobar'));
        $promise->onResolve(function() use($data) {
            $data->data[] = 2;
        });

        yield new Delayed(0);

        $this->assertSame([1,2], $data->data);
    }

    public function testListenerThrowsException() {
        $exception = new \Exception('Listener thrown exception');
        $subject = new AmpEventEmitter();
        $subject->on('foobar', function() use($exception) {
            throw $exception;
        });

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Listener thrown exception');
        yield $subject->emit($this->standardEvent('foobar'));
    }

    public function testListenerThrowsExceptionDoesNotStopOtherListeners() {
        $data = new \stdClass();
        $data->data = [];
        $subject = new AmpEventEmitter();
        $subject->on('foobar', function() use($data) {
            $data->data[] = 'before';
        });
        $subject->on('foobar', function() {
            throw new \Exception('Listener thrown exception');
        });
        $subject->on('foobar', function() use($data) {
            $data->data[] = 'after';
        });


        $promise = $subject->emit($this->standardEvent('foobar'));
        $promise->onResolve(function() use($data) {
            $this->assertSame(['before', 'after'], $data->data);
        });

        yield new Delayed(0);
    }

    public function testListenerReturnsResolvedValues() {
        $subject = new AmpEventEmitter();
        $subject->on('something', function() {
            return 1;
        });
        $subject->on('something', function() {
            $deferred = new Deferred();
            Loop::defer(function() use($deferred) {
                $deferred->resolve(2);
            });
            return $deferred->promise();
        });
        $subject->on('something', function() {
            yield new Delayed(0);
            yield new Delayed(0);
            return 3;
        });

        $result = yield $subject->emit($this->standardEvent('something'));
        $this->assertSame([1,2,3], $result);
    }

    public function testListenerArgumentsCorrect() {
        $subject = new AmpEventEmitter();
        $data = new \stdClass();
        $data->data = [];
        $listenerId = $subject->on('something', function($event, $listenerData) use($data) {
            $data->data[] = $event;
            $data->data[] = $listenerData;
        });

        yield $subject->emit($this->standardEvent('something'));

        $this->assertInstanceOf(Event::class, $data->data[0]);
        $this->assertSame(['__labrador_kennel_id' => $listenerId], $data->data[1]);
    }

    public function testEventArgumentHasCorrectInformation() {
        $subject = new AmpEventEmitter();
        $target = new \stdClass();
        $subject->on('something', function(Event $event) use($target) {
            $this->assertSame('something', $event->name());
            $this->assertSame($target, $event->target());
            $this->assertSame([1,2,3], $event->data());
        });

        yield $subject->emit($this->standardEvent('something', $target, [1, 2, 3]));
    }

    public function testEventListenerDataHasCorrectInformation() {
        $subject = new AmpEventEmitter();
        $data = new \stdClass();
        $data->data = [];
        $id = $subject->on('something', function($event, $listenerData) use($data) {
            $data->data = $listenerData;
        }, [1,2,3]);

        yield $subject->emit($this->standardEvent('something'));

        $this->assertSame([1,2,3, '__labrador_kennel_id' => $id], $data->data);
    }

    public function testRunningListenerOnce() {
        $data = new \stdClass();
        $data->data = [];
        $subject = new AmpEventEmitter();
        $subject->once('something', function() use($data) {
            $data->data[] = 1;
        });

        $subject->setDefaultPromiseCombinator(PromiseCombinator::All());

        yield $subject->emit($this->standardEvent('something'));
        yield $subject->emit($this->standardEvent('something'));
        $this->assertSame([1], $data->data);
    }

    public function testEmittingEventOnceAsynchronously() {
        $data = new \stdClass();
        $data->data = [];
        $subject = new AmpEventEmitter();
        $subject->once('foo', function() use($data) {
            $data->data[] = 1;
            yield new Delayed(0);
            $data->data[] = 3;
            yield new Delayed(0);
            $data->data[] = 5;
        });

        $subject->once('foo', function() use($data) {
            $data->data[] = 2;
            yield new Delayed(0);
            $data->data[] = 4;
            yield new Delayed(0);
            $data->data[] = 6;
        });

        yield $subject->emit($this->standardEvent('foo'));

        $this->assertSame([1,2,3,4,5,6], $data->data);
    }

    public function testEmittingRespectsPassedPromiseCombinator() {
        $data = new \stdClass();
        $data->data = [];
        $subject = new AmpEventEmitter();
        $subject->on('foo', function() use($data) {
            $data->data[] = 0;
            return new Success();
        });
        $subject->on('foo', function() use($data) {
            yield new Delayed(0);
            $data->data[] = 1;
            return new Success();
        });

        yield $subject->emit($this->standardEvent('foo'), PromiseCombinator::First());

        $this->assertEquals([0], $data->data);
    }

    public function testEmittingRespectsDefaultPromiseCombinatorIfNoneProvided() {
        $data = new \stdClass();
        $data->data = [];
        $subject = new AmpEventEmitter();
        $subject->on('foo', function() use($data) {
            $data->data[] = 0;
            return new Success();
        });
        $subject->on('foo', function() use($data) {
            yield new Delayed(0);
            $data->data[] = 1;
            return new Failure(new \RuntimeException('Thrown exception'));
        });

        $subject->setDefaultPromiseCombinator(PromiseCombinator::All());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Thrown exception');

        yield $subject->emit($this->standardEvent('foo'));
    }
}
