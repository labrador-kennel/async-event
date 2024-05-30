<?php declare(strict_types=1);

namespace Labrador\AsyncEvent;

use DateTimeImmutable;

/**
 * @template Payload of object
 * @implements Event<Payload>
 * @api
 */
abstract class AbstractEvent implements Event {

    private readonly DateTimeImmutable $triggeredAt;

    /**
     * @param non-empty-string $name
     * @param Payload $payload
     */
    protected function __construct(
        private readonly string $name,
        private readonly object $payload
    ) {
        $this->triggeredAt = new DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function name() : string {
        return $this->name;
    }

    /**
     * @return Payload
     */
    public function payload() : object {
        return $this->payload;
    }

    public function triggeredAt() : DateTimeImmutable {
        return $this->triggeredAt;
    }
}
