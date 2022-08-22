<?php

namespace Cspray\Labrador\AsyncEvent\DependencyInjection;

enum ListenerRemoval : string {
    case NeverRemove = 'never';
    case AfterOneEvent = 'one';
}
