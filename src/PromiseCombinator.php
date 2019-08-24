<?php declare(strict_types=1);

namespace Cspray\Labrador\AsyncEvent;

use Amp\Promise;
use Cspray\Yape\Enum;

use function Amp\Promise\all;
use function Amp\Promise\any;
use function Amp\Promise\first;
use function Amp\Promise\some;

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

    public static function All() : PromiseCombinator {
        return self::getSingleton('All', 'All', function(Promise ...$promises) {
            return all($promises);
        });
    }

    public static function Any() : PromiseCombinator {
        return self::getSingleton('Any', 'Any', function(Promise ...$promises) {
            return any($promises);
        });
    }

    public static function First() : PromiseCombinator {
        return self::getSingleton('First', 'First', function(Promise ...$promises) {
            return first($promises);
        });
    }

    public static function Some() : PromiseCombinator {
        return self::getSingleton('Some', 'Some', function(Promise ...$promises) {
            return some($promises);
        });
    }

    public function getValue() : string {
        return $this->value;
    }

    public function combine(Promise ...$promises) : Promise {
        return ($this->combinator)(...$promises);
    }

    public function equals(PromiseCombinator $promiseCombinator) : bool {
        return $this === $promiseCombinator;
    }

    public function toString() : string {
        return get_class($this) . '@' . $this->enumConstName;
    }
}
