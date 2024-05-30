<?php declare(strict_types=1);

namespace Labrador\AsyncEvent\Test\Unit;

use DateTimeImmutable;
use Labrador\AsyncEvent\AbstractEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(AbstractEvent::class)]
final class AbstractEventTest extends TestCase {

    public function event(string $name, object $payload) : AbstractEvent {
        return new class ($name, $payload) extends AbstractEvent {
            public function __construct(string $name, object $payload) {
                parent::__construct($name, $payload);
            }
        };
    }

    public function testEventNameReturnedProperly() : void {
        $subject = $this->event('name', new stdClass());
        self::assertSame('name', $subject->name());
    }

    public function testEventPayloadReturnedCorrectly() : void {
        $subject = $this->event('whatever', $expected = new stdClass());
        self::assertSame($expected, $subject->payload());
    }

    public function testEventDateTimeCreatedWithCorrectUtcTimestamp() : void {
        $now = new DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $subject = $this->event('whatever', new stdClass());
        self::assertEqualsWithDelta($now, $subject->triggeredAt(), 0.025);
    }
}
