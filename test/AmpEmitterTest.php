<?php declare(strict_types=1);

namespace Cspray\Labrador\AsyncEvent\Test;

use Amp\Deferred;
use Amp\Delayed;
use Amp\Loop;
use Cspray\Labrador\AsyncEvent\AmpEmitter;
use Cspray\Labrador\AsyncEvent\Event;
use PHPUnit\Framework\TestCase as UnitTestCase;

class AmpEmitterTest extends UnitTestCase {

    public function testRegisteringEventListenerIncrementsListenerCount() {
        $subject = new AmpEmitter();
        $subject->on('something', function() {});

        $this->assertSame(1, $subject->listenerCount('something'));

        $subject->on('something', function() {});

        $this->assertSame(2, $subject->listenerCount('something'));
    }

    public function testRemovingEventListenerDecrementsListenerCount() {
        $subject = new AmpEmitter();
        $one = $subject->on('something', function() {});
        $two = $subject->on('something', function() {});

        $this->assertSame(2, $subject->listenerCount('something'));

        $subject->off($one);

        $this->assertSame(1, $subject->listenerCount('something'));

        $subject->off($two);

        $this->assertSame(0, $subject->listenerCount('something'));
    }

    public function testRegisteringEventListenerReturnsCorrectIdFormat() {
        $subject = new AmpEmitter();
        $id = $subject->on('something', function() {});

        $this->assertStringMatchesFormat('something:%x', $id);
    }

    public function testRegisteringEventListenerIsReturnedInListeners() {
        $subject = new AmpEmitter();
        $callbackOne = function() { };
        $callbackTwo = function() { };

        $idOne = explode(':', $subject->on('something', $callbackOne))[1];
        $idTwo = explode(':', $subject->on('something', $callbackTwo))[1];

        $this->assertSame([$idOne => [$callbackOne, []], $idTwo => [$callbackTwo, []]], $subject->listeners('something'));
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

        Loop::run(function() use($subject) {
            $subject->emit('foo', new \stdClass());
        });

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

        Loop::run(function() use($subject) {
            $subject->emit('foo', new \stdClass());
        });

        $this->assertSame([1,2,3,4,5,6], $data->data);
    }

    public function testEmitPromiseResolves() {
        $data = new \stdClass();
        $data->data = [];
        $subject = new AmpEmitter();
        $subject->on('foo', function() use($data) {
            $data->data[] = 1;
        });

        Loop::run(function() use($subject, $data) {
            $promise = $subject->emit('foo', new \stdClass());
            $promise->onResolve(function() use($data) {
                $data->data[] = 2;
            });
        });

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

        Loop::run(function() use($subject, $data) {
            $promise = $subject->emit('foobar', new \stdClass());
            $promise->onResolve(function() use($data) {
                $data->data[] = 2;
            });
        });


        $this->assertSame([1,2], $data->data);
    }

    public function testListenerThrowsException() {
        $exception = new \Exception('Listener thrown exception');
        $subject = new AmpEmitter();
        $subject->on('foobar', function() use($exception) {
            throw $exception;
        });

        Loop::run(function() use($subject, $exception) {
            $promise = $subject->emit('foobar', new \stdClass());
            $promise->onResolve(function(?\Throwable $error, ?array $result = null) use($exception) {
                $this->assertSame($result[0][0]->getMessage(), $exception->getMessage());
            });
        });
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


        Loop::run(function() use($subject, $data) {
            $promise = $subject->emit('foobar', new \stdClass());
            $promise->onResolve(function() use($data) {
                $this->assertSame(['before', 'after'], $data->data);
            });
        });
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

        Loop::run(function() use($subject) {
            $promise = $subject->emit('something', new \stdClass());
            $promise->onResolve(function($error = null, $result) {
                $this->assertSame([1,2,3], $result[1]);
            });
        });
    }

    public function testListenerArgumentsCorrect() {
        $subject = new AmpEmitter();
        $data = new \stdClass();
        $data->data = [];
        $listenerId = $subject->on('something', function($event, $listenerData) use($data) {
            $data->data[] = $event;
            $data->data[] = $listenerData;
        });

        Loop::run(function() use($subject) {
            yield $subject->emit('something', new \stdClass());
        });

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

        Loop::run(function() use($subject) {
            yield $subject->emit('something', new \stdClass, [1,2,3]);
        });
    }

    public function testEventListenerDataHasCorrectInformation() {
        $subject = new AmpEmitter();
        $data = new \stdClass();
        $data->data = [];
        $id = $subject->on('something', function($event, $listenerData) use($data) {
            $data->data = $listenerData;
        }, [1,2,3]);

        Loop::run(function() use($subject) {
            yield $subject->emit('something', new \stdClass());
        });
        $this->assertSame([1,2,3,'id' => $id], $data->data);
    }

    public function testRunningListenerOnce() {
        $data = new \stdClass();
        $data->data = [];
        $subject = new AmpEmitter();
        $subject->once('something', function() use($data) {
            $data->data[] = 1;
        });

        Loop::run(function() use($subject, $data) {
            yield $subject->emit('something', new \stdClass());
            yield $subject->emit('something', new \stdClass());
        });
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

        Loop::run(function() use($subject) {
            $subject->emit('foo', new \stdClass());
        });

        $this->assertSame([1,2,3,4,5,6], $data->data);
    }

}