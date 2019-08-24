<?php declare(strict_types=1);

namespace Cspray\Labrador\AsyncEvent\Test;

use Amp\Deferred;
use Amp\Delayed;
use Amp\Failure;
use Amp\Loop;
use Amp\PHPUnit\AsyncTestCase;
use Amp\Success;
use Cspray\Labrador\AsyncEvent\AmpEmitter;
use Cspray\Labrador\AsyncEvent\Event;
use Cspray\Labrador\AsyncEvent\PromiseCombinator;
use Cspray\Labrador\AsyncEvent\StandardEvent;

class AmpEmitterTest extends AsyncTestCase {

    private function standardEvent(string $name, $target = null, array $eventData = []) {
        $target = $target ?? new \stdClass();
        return new StandardEvent($name, $target, $eventData);
    }

    public function testDefaultPromiseCombinatorIsAny() {
        $subject = new AmpEmitter();

        $this->assertEquals(PromiseCombinator::Any(), $subject->getDefaultPromiseCombinator());
    }

    public function testSettingDefaultPromiseCombinator() {
        $subject = new AmpEmitter();

        $subject->setDefaultPromiseCombinator(PromiseCombinator::Some());

        $this->assertEquals(PromiseCombinator::Some(), $subject->getDefaultPromiseCombinator());
    }

    public function testRegisteringEventListenerIncrementsListenerCount() {
        $subject = new AmpEmitter();
        $subject->on('something', function() {
        });

        $this->assertSame(1, $subject->listenerCount('something'));

        $subject->on('something', function() {
        });

        $this->assertSame(2, $subject->listenerCount('something'));
    }

    public function testRemovingEventListenerDecrementsListenerCount() {
        $subject = new AmpEmitter();
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
        $subject = new AmpEmitter();
        $id = $subject->on('something', function() {
        });

        $this->assertStringMatchesFormat('something:%x', $id);
    }

    public function testRegisteringEventListenerIsReturnedInListeners() {
        $subject = new AmpEmitter();
        $callbackOne = function() {
        };
        $callbackTwo = function() {
        };

        $idOne = explode(':', $subject->on('something', $callbackOne))[1];
        $idTwo = explode(':', $subject->on('something', $callbackTwo))[1];
        $expected = [$idOne => [$callbackOne, []], $idTwo => [$callbackTwo, []]];
        $this->assertSame($expected, $subject->listeners('something'));
    }

    public function testListenerCountWithNoRegisteredListeners() {
        $subject = new AmpEmitter();

        $this->assertSame(0, $subject->listenerCount('something'));
    }

    public function testListenersWithNoRegisteredListeners() {
        $subject = new AmpEmitter();

        $this->assertSame([], $subject->listeners('something'));
    }

    public function testEmittingEvent() {
        $data = new \stdClass();
        $data->data = [];
        $subject = new AmpEmitter();
        $subject->on('foo', function() use($data) {
            $data->data[] = 'foo';
        });

        yield $subject->emit($this->standardEvent('foo'));

        $this->assertSame(['foo'], $data->data);
    }

    public function testEmittingEventAsynchronously() {
        $data = new \stdClass();
        $data->data = [];
        $subject = new AmpEmitter();
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
        $subject = new AmpEmitter();
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
        $subject = new AmpEmitter();
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
        $subject = new AmpEmitter();
        $subject->on('foobar', function() use($exception) {
            throw $exception;
        });

        $promise = $subject->emit($this->standardEvent('foobar'));
        $promise->onResolve(function(?\Throwable $error, ?array $result = null) use($exception) {
            $this->assertSame($result[0][0]->getMessage(), $exception->getMessage());
        });

        yield new Delayed(0);
    }

    public function testListenerThrowsExceptionDoesNotStopOtherListeners() {
        $data = new \stdClass();
        $data->data = [];
        $subject = new AmpEmitter();
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
        $subject = new AmpEmitter();
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

        $promise = $subject->emit($this->standardEvent('something'));
        $promise->onResolve(function($error, $result) {
            $this->assertSame([1,2,3], $result[1]);
        });

        yield new Delayed(0);
    }

    public function testListenerArgumentsCorrect() {
        $subject = new AmpEmitter();
        $data = new \stdClass();
        $data->data = [];
        $listenerId = $subject->on('something', function($event, $listenerData) use($data) {
            $data->data[] = $event;
            $data->data[] = $listenerData;
        });

        yield $subject->emit($this->standardEvent('something'));

        $this->assertInstanceOf(Event::class, $data->data[0]);
        $this->assertSame(['id' => $listenerId], $data->data[1]);
    }

    public function testEventArgumentHasCorrectInformation() {
        $subject = new AmpEmitter();
        $target = new \stdClass();
        $subject->on('something', function(Event $event) use($target) {
            $this->assertSame('something', $event->name());
            $this->assertSame($target, $event->target());
            $this->assertSame([1,2,3], $event->data());
        });

        yield $subject->emit($this->standardEvent('something'));
    }

    public function testEventListenerDataHasCorrectInformation() {
        $subject = new AmpEmitter();
        $data = new \stdClass();
        $data->data = [];
        $id = $subject->on('something', function($event, $listenerData) use($data) {
            $data->data = $listenerData;
        }, [1,2,3]);

        yield $subject->emit($this->standardEvent('something'));

        $this->assertSame([1,2,3,'id' => $id], $data->data);
    }

    public function testRunningListenerOnce() {
        $data = new \stdClass();
        $data->data = [];
        $subject = new AmpEmitter();
        $subject->once('something', function() use($data) {
            $data->data[] = 1;
        });

        yield $subject->emit($this->standardEvent('something'));
        yield $subject->emit($this->standardEvent('something'));
        $this->assertSame([1], $data->data);
    }

    public function testEmittingEventOnceAsynchronously() {
        $data = new \stdClass();
        $data->data = [];
        $subject = new AmpEmitter();
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
        $subject = new AmpEmitter();
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
        $subject = new AmpEmitter();
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
