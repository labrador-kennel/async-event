<?php declare(strict_types=1);

namespace Cspray\Labrador\AsyncEvent\Test;

use Amp\PHPUnit\AsyncTestCase;
use Cspray\Labrador\AsyncEvent\AmpEventEmitter;
use Cspray\Labrador\AsyncEvent\Event;
use Cspray\Labrador\AsyncEvent\StandardEvent;
use Exception;
use function Amp\delay;

class AmpEventEmitterTest extends AsyncTestCase {

    private function standardEvent(string $name, $target = null, array $eventData = []) {
        $target = $target ?? new \stdClass();
        return new StandardEvent($name, $target, $eventData);
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

        $subject->emit($this->standardEvent('foo'))->await();

        $this->assertSame(['foo'], $data->data);
    }

    public function testEmittingEventAsynchronously() {
        $data = new \stdClass();
        $data->data = [];
        $subject = new AmpEventEmitter();
        $subject->on('foo', function() use($data) {
            $data->data[] = 1;
            delay(0);
            $data->data[] = 3;
            delay(0);
            $data->data[] = 5;
        });

        $subject->on('foo', function() use($data) {
            $data->data[] = 2;
            delay(0);
            $data->data[] = 4;
            delay(0);
            $data->data[] = 6;
        });

        $subject->emit($this->standardEvent('foo'))->await();

        $this->assertSame([1,2,3,4,5,6], $data->data);
    }

    public function testListenerThrowsException() {
        $exception = new Exception('Listener thrown exception');
        $subject = new AmpEventEmitter();
        $subject->on('foobar', function() use($exception) {
            throw $exception;
        });

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Listener thrown exception');
        $subject->emit($this->standardEvent('foobar'))->await();
    }

    public function testListenerThrowsExceptionDoesNotStopOtherListeners() {
        $badException1 = new Exception('Listener thrown exception');
        $badException2 = new Exception('Another listener thrown exception');
        $data = new \stdClass();
        $data->data = [];
        $subject = new AmpEventEmitter();
        $subject->on('foobar', function() use($data) {
            $data->data[] = 'before';
        });
        $badId1 = $subject->on('foobar', function() use($badException1) {
            throw $badException1;
        });
        $badId2 = $subject->on('foobar', function() use($badException2) {
            throw $badException2;
        });
        $subject->on('foobar', function() use($data) {
            $data->data[] = 'after';
        });

        [$errors,] = $subject->emit($this->standardEvent('foobar'))->awaitAll();
        $expected = [
            $badId1 => $badException1,
            $badId2 => $badException2
        ];
        $this->assertEquals($expected, $errors);
        $this->assertSame(['before', 'after'], $data->data);
    }

    public function testListenerArgumentsCorrect() {
        $subject = new AmpEventEmitter();
        $data = new \stdClass();
        $data->data = [];
        $listenerId = $subject->on('something', function($event, $listenerData) use($data) {
            $data->data[] = $event;
            $data->data[] = $listenerData;
        });

        $subject->emit($this->standardEvent('something'))->await();

        $this->assertInstanceOf(Event::class, $data->data[0]);
        $this->assertSame(['__labrador_kennel_id' => $listenerId], $data->data[1]);
    }

    public function testEventArgumentHasCorrectInformation() {
        $subject = new AmpEventEmitter();
        $target = new \stdClass();
        $subject->on('something', function(Event $event) use($target) {
            $this->assertSame('something', $event->getName());
            $this->assertSame($target, $event->getTarget());
            $this->assertSame([1,2,3], $event->getData());
        });

        $subject->emit($this->standardEvent('something', $target, [1, 2, 3]))->await();
    }

    public function testEventListenerDataHasCorrectInformation() {
        $subject = new AmpEventEmitter();
        $data = new \stdClass();
        $data->data = [];
        $id = $subject->on('something', function($event, $listenerData) use($data) {
            $data->data = $listenerData;
        }, [1,2,3]);

        $subject->emit($this->standardEvent('something'))->await();

        $this->assertSame([1,2,3, '__labrador_kennel_id' => $id], $data->data);
    }

    public function testRunningListenerOnce() {
        $data = new \stdClass();
        $data->data = [];
        $subject = new AmpEventEmitter();
        $subject->once('something', function() use($data) {
            $data->data[] = 1;
        });

        $subject->emit($this->standardEvent('something'))->await();
        $subject->emit($this->standardEvent('something'))->await();
        $this->assertSame([1], $data->data);
    }

    public function testEmittingEventOnceAsynchronously() {
        $data = new \stdClass();
        $data->data = [];
        $subject = new AmpEventEmitter();
        $subject->once('foo', function() use($data) {
            $data->data[] = 1;
            delay(0);
            $data->data[] = 3;
            delay(0);
            $data->data[] = 5;
        });

        $subject->once('foo', function() use($data) {
            $data->data[] = 2;
            delay(0);
            $data->data[] = 4;
            delay(0);
            $data->data[] = 6;
        });

        $subject->emit($this->standardEvent('foo'))->await();

        $this->assertSame([1,2,3,4,5,6], $data->data);
    }

}
