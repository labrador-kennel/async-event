<?php declare(strict_types=1);

namespace Labrador\AsyncEvent\Test\Unit\Stub;

use Closure;
use Labrador\AsyncEvent\AbstractListenerProvider;

class StubAbstractListenerProvider extends AbstractListenerProvider {

    public function __construct(array $events, Closure $closure) {
        parent::__construct($events, $closure);
    }
}
