<?php declare(strict_types=1);

namespace Cspray\Labrador\AsyncEvent;

interface Event {

    public function name() : string;

    public function target();

    public function data() : array;

    public function createdAt() : \DateTimeImmutable;
}
