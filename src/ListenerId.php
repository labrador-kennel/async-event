<?php declare(strict_types=1);

namespace Cspray\Labrador\AsyncEvent;

/**
 *
 * @package Cspray\Labrador\AsyncEvent
 * @license See LICENSE in source root
 */
interface ListenerId {

    public function getEventName() : string;

    public function getListenerId() : string;

}