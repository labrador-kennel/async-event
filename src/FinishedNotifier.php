<?php declare(strict_types=1);

namespace Labrador\AsyncEvent;

use Amp\CompositeException;

/**
 * @api
 */
interface FinishedNotifier {

    /**
     * @param callable(?CompositeException, array<array-key, mixed>):void $callable
     * @return void
     */
    public function finished(callable $callable) : void;

}