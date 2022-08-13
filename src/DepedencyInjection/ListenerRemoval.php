<?php

namespace Cspray\Labrador\AsyncEvent\DepedencyInjection;

enum ListenerRemoval : string {
    case NeverRemove = 'never';
    case AfterOneEvent = 'one';
}