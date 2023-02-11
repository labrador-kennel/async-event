<?php

namespace Labrador\AsyncEvent\Test\Unit;

use Labrador\AsyncEvent\DependencyInjection\AsyncEventInitializer;
use Labrador\AsyncEvent\DependencyInjection\AutowireObserver;
use PHPUnit\Framework\TestCase;

final class AsyncEventInitializerTest extends TestCase {

    public function testGetPackageName() : void {
        $actual = (new AsyncEventInitializer())->getPackageName();

        self::assertSame('labrador-kennel/async-event', $actual);
    }

    public function testGetScanDirectories() : void {
        $actual = (new AsyncEventInitializer())->getRelativeScanDirectories();

        self::assertSame(['src'], $actual);
    }

    public function testGetObservers() : void {
        $actual = (new AsyncEventInitializer())->getObserverClasses();

        self::assertSame([AutowireObserver::class], $actual);
    }

    public function testGetDefinitionProvider() : void {
        $actual = (new AsyncEventInitializer())->getDefinitionProviderClass();

        self::assertNull($actual);
    }
}
