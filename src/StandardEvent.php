<?php declare(strict_types=1);

namespace Cspray\Labrador\AsyncEvent;

use DateTimeInterface;
use DateTimeImmutable;

/**
 * An Event implementation that is used by the StandardEventFactory as a fallback Event in the case where a custom
 * event factory has not been registered for the given event name.
 *
 * @package Cspray\Labrador\AsyncEvent
 */
class StandardEvent implements Event {

    private string $name;
    private object $target;
    private array $data;
    private DateTimeImmutable $createdAt;

    public function __construct(string $name, object $target, array $data = []) {
        $this->name = $name;
        $this->target = $target;
        $this->data = $data;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getName() : string {
        return $this->name;
    }

    public function getTarget() : object {
        return $this->target;
    }

    public function getData() : array {
        return $this->data;
    }

    public function getCreatedAt() : DateTimeImmutable {
        return $this->createdAt;
    }
}
