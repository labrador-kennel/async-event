<?php

namespace Cspray\Labrador\AsyncEvent\Test\Integration\Listeners;

use Amp\Future;
use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\Labrador\AsyncEvent\AbstractListener;
use Cspray\Labrador\AsyncEvent\Event;
use Labrador\CompositeFuture\CompositeFuture;

#[Service]
class NotAutowiredListener extends AbstractListener {

    public function __construct() {
        parent::__construct('something');
    }

    public function handle(Event $event) : Future|CompositeFuture|null {
        return null;
    }
}