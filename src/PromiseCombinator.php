<?php declare(strict_types=1);

namespace Cspray\Labrador\AsyncEvent;

use Amp\Promise;
use Cspray\Yape\Enum;
use Cspray\Yape\EnumTrait;

final class PromiseCombinator implements Enum {

    use EnumTrait;

    /**
     * @var callable
     */
    private $combinator;

    private function __construct(string $value, callable $combinator) {
        $this->setEnumValue($value);
        $this->combinator = $combinator;
    }

    public static function All() : self {
        return self::getSingleton(__FUNCTION__, function(Promise ...$promises) {
            return Promise\all($promises);
        });
    }

    public static function Any() : self {
        return self::getSingleton(__FUNCTION__, function(Promise ...$promises) {
            return Promise\any($promises);
        });
    }

    public static function First() : self {
        return self::getSingleton(__FUNCTION__, function(Promise ...$promises) {
            return Promise\first($promises);
        });
    }

    public static function Some() : self {
        return self::getSingleton(__FUNCTION__, function(Promise ...$promises) {
            return Promise\some($promises);
        });
    }

    // It is imperative that if you add a new value post code generation you add the method name here!
    protected static function getAllowedValues() : array {
        return ['All', 'Any', 'First', 'Some', ];
    }

    public function combine(Promise ...$promises) : Promise {
        return ($this->combinator)(...$promises);
    }
}
