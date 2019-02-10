<?php declare(strict_types=1);

namespace Cspray\Labrador\AsyncEvent;

class StandardEvent implements Event {

    private $name;
    private $target;
    private $data;
    private $createdAt;

    public function __construct(string $name, $target, array $data = []) {
        $this->name = $name;
        $this->target = $target;
        $this->data = $data;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function name() : string {
        return $this->name;
    }

    public function target() {
        return $this->target;
    }

    public function data() : array {
        return $this->data;
    }

    public function createdAt() : \DateTimeImmutable {
        return $this->createdAt;
    }
}
