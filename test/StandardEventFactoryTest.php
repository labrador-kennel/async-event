<?php

declare(strict_types = 1);

/**
 * @license See LICENSE file in project root
 */

namespace Cspray\Labrador\AsyncEvent\Test;

use Cspray\Labrador\AsyncEvent\StandardEvent;
use Cspray\Labrador\AsyncEvent\StandardEventFactory;
use Cspray\Labrador\Exception\InvalidTypeException;
use Cspray\Labrador\AsyncEvent\Event;
use Cspray\Labrador\AsyncEvent\Test\Stub\FooEventStub;

use PHPUnit\Framework\TestCase as UnitTestCase;

class StandardEventFactoryTest extends UnitTestCase {

    public function testMakingCustomEvent() {
        $factory = new StandardEventFactory();
        $factory->register('foo.event', function($target, array $eventData = []) {
            return new FooEventStub($target, $eventData);
        });

        $this->assertInstanceOf(
            FooEventStub::class,
            $factory->create('foo.event', new \stdClass())
        );
    }

    public function testObjectMustBeInstanceOfEvent() {
        $factory = new StandardEventFactory();
        $factory->register('bar.event', function() {
            return 'not an EventInterface';
        });

        $this->expectException(InvalidTypeException::class);
        $msg = 'Factory functions MUST return an instance of ' . Event::class . ' but "bar.event" returned "string".';
        $this->expectExceptionMessage($msg);

        $factory->create('bar.event', new \stdClass());
    }

    public function testEventNameMustMatch() {
        $factory = new StandardEventFactory();
        $factory->register('bar.event', function() {
            return new StandardEvent('not.bar.event', new \stdClass());
        });

        $this->expectException(InvalidTypeException::class);
        $msg = 'Factory functions MUST return an instance of ' . Event::class . ' with the same name as "bar.event"';
        $this->expectExceptionMessage($msg);

        $factory->create('bar.event', new \stdClass());
    }

    public function testEventNameNotRegisteredReturnsStandardEvent() {
        $factory = new StandardEventFactory();

        $event = $factory->create('foo.bar', $target = new \stdClass());

        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame('foo.bar', $event->getName());
        $this->assertSame($target, $event->getTarget());
        $this->assertSame([], $event->getData());
    }
}
