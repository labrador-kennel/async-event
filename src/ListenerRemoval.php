<?php

namespace Labrador\AsyncEvent;

enum ListenerRemoval : string {
    case NeverRemove = 'never';
    case AfterOneEvent = 'one';
}
