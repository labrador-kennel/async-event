<?php declare(strict_types=1);

namespace Cspray\Labrador\AsyncEvent;

use Amp\Promise;
use Cspray\Yape\Enum;

use function Amp\Promise\all;
use function Amp\Promise\any;
use function Amp\Promise\first;
use function Amp\Promise\some;

/**
 * An Enum that represents how an EventEmitter will combine the Promises that event listeners generate.
 *
 * @package Cspray\Labrador\AsyncEvent
 * @license See LICENSE in source root
 */
final class PromiseCombinator implements Enum {

    private static $container = [];

    private $enumConstName;
    private $value;
    private $combinator;

    private function __construct(string $enumConstName, string $value, callable $combinator) {
        $this->enumConstName = $enumConstName;
        $this->value = $value;
        $this->combinator = $combinator;
    }

    protected static function getSingleton($value, ...$additionalConstructorArgs) {
        if (!isset(self::$container[$value])) {
            self::$container[$value] = new self(...array_merge([$value], $additionalConstructorArgs));
        }

        return self::$container[$value];
    }

    /**
     * A PromiseCombinator that uses Amp\Promise\all to combine the Promises.
     *
     * @return PromiseCombinator
     */
    public static function All() : PromiseCombinator {
        return self::getSingleton('All', '\\Amp\\Promise\\all', function(Promise ...$promises) {
            return all($promises);
        });
    }

    /**
     * A PromiseCombinator that uses Amp\Promise\any to combine the Promises.
     *
     * @return PromiseCombinator
     */
    public static function Any() : PromiseCombinator {
        return self::getSingleton('Any', '\\Amp\\Promise\\any', function(Promise ...$promises) {
            return any($promises);
        });
    }

    /**
     * A PromiseCombinator that uses Amp\Promise\first to combine the Promises.
     *
     * @return PromiseCombinator
     */
    public static function First() : PromiseCombinator {
        return self::getSingleton('First', '\\Amp\\Promise\\first', function(Promise ...$promises) {
            return first($promises);
        });
    }

    /**
     * A PromiseCombinator that uses Amp\Promise\some to combine the Promises.
     *
     * @return PromiseCombinator
     */
    public static function Some() : PromiseCombinator {
        return self::getSingleton('Some', '\\Amp\\Promise\\some', function(Promise ...$promises) {
            return some($promises);
        });
    }

    /**
     * Returns the fully qualified method name that this PromiseCombinator represents.
     *
     * @return string
     */
    public function getValue() : string {
        return $this->value;
    }

    /**
     * Combines the Promises using the Amp Promise function this enum represents.
     *
     * @param Promise ...$promises
     * @return Promise
     */
    public function combine(Promise ...$promises) : Promise {
        return ($this->combinator)(...$promises);
    }

    /**
     * @param PromiseCombinator $promiseCombinator
     * @return bool
     */
    public function equals(PromiseCombinator $promiseCombinator) : bool {
        return $this === $promiseCombinator;
    }

    /**
     * @return string
     */
    public function toString() : string {
        return get_class($this) . '@' . $this->enumConstName;
    }
}
