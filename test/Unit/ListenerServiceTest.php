<?php declare(strict_types=1);

namespace Labrador\AsyncEvent\Test\Unit;

use Labrador\AsyncEvent\ListenerRemoval;
use Labrador\AsyncEvent\ListenerService;
use PHPUnit\Framework\TestCase;

class ListenerServiceTest extends TestCase {

    public function testListenerRemovalDefaultsToNeverRemoved() : void {
        $subject = new ListenerService();

        self::assertSame(ListenerRemoval::NeverRemove, $subject->getListenerRemoval());
    }

    public function testListenerRemovalRespectsPassedInValues() : void {
        $subject = new ListenerService(ListenerRemoval::AfterOneEvent);

        self::assertSame(ListenerRemoval::AfterOneEvent, $subject->getListenerRemoval());
    }

    public function testProfilesDefaultsToEmpty() : void {
        $subject = new ListenerService();

        self::assertSame([], $subject->getProfiles());
    }

    public function testProfilesRespectsProvidedValues() : void {
        $subject = new ListenerService(profiles: ['foo', 'bar']);

        self::assertSame(['foo', 'bar'], $subject->getProfiles());
    }

    public function testNameDefaultsToNull() : void {
        $subject = new ListenerService();

        self::assertNull($subject->getName());
    }

    public function testNameRespectsProvidedValue() : void {
        $subject = new ListenerService(name: 'my-listener');

        self::assertSame('my-listener', $subject->getName());
    }

    public function testIsPrimaryAlwaysFalse() : void {
        $subject = new ListenerService();

        self::assertFalse($subject->isPrimary());
    }
}
