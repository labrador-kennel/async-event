<?php declare(strict_types=1);

namespace Labrador\AsyncEvent;

interface EventName {

    /**
     * @return non-empty-string
     */
    public function name() : string;

}