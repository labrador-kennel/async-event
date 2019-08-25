<?php declare(strict_types=1);

namespace Cspray\Labrador\AsyncEvent;

/**
 * Abstraction over the concept of a listener id being multiple pieces of data.
 *
 * @package Cspray\Labrador\AsyncEvent
 * @license See LICENSE in source root
 */
interface ListenerId {

    /**
     * The event that the listener is corresponded to.
     *
     * @return string
     */
    public function getEventName() : string;

    /**
     * An internally generated string that represents this unique listener; typically this is a randomly generated
     * string.
     *
     * @return string
     */
    public function getListenerId() : string;

}