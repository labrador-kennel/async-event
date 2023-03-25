<?php declare(strict_types=1);

namespace Labrador\AsyncEvent;

interface ListenerProvider {

    public function getListener() : Listener;
}
