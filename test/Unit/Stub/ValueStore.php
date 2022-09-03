<?php

namespace Labrador\AsyncEvent\Test\Unit\Stub;

class ValueStore {

    /**
     * @var list<int>
     */
    private array $store = [];

    public function add(int $value) : void {
        $this->store[] = $value;
    }

    /**
     * @return list<int>
     */
    public function getValues() : array {
        return $this->store;
    }
}
