<?php

declare(strict_types = 1);

/**
 * @license See LICENSE file in project root
 */

namespace Labrador\AsyncEvent\Test\Unit\Stub;

use Labrador\AsyncEvent\StandardEvent;

class FooEventStub extends StandardEvent {

    public function __construct($target) {
        parent::__construct('foo.event', $target);
    }
}
