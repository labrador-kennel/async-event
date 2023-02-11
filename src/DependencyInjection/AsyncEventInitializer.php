<?php

namespace Labrador\AsyncEvent\DependencyInjection;

use Cspray\AnnotatedContainer\Bootstrap\ThirdPartyInitializer;

final class AsyncEventInitializer extends ThirdPartyInitializer {

    public function getPackageName() : string {
        return 'labrador-kennel/async-event';
    }

    public function getRelativeScanDirectories() : array {
        return ['src'];
    }

    public function getObserverClasses() : array {
        return [
            AutowireObserver::class
        ];
    }

    public function getDefinitionProviderClass() : ?string {
        return null;
    }
}