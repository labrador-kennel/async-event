<?php declare(strict_types=1);

namespace Labrador\AsyncEvent\Test\Unit\Helper;

use Labrador\AsyncEvent\EventName;

enum MyEnum : string implements EventName {
    case Foo = 'foo';

    public function name() : string {
        return $this->value;
    }
}
