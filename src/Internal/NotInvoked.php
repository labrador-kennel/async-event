<?php declare(strict_types=1);

namespace Labrador\AsyncEvent\Internal;

use PHPUnit\Framework\Attributes\CodeCoverageIgnore;

/**
 * @internal
 */
final class NotInvoked {

    /** @codeCoverageIgnore  */
    private function __construct() {}

    public static function create() : self {
        /** @var NotInvoked|null $instance */
        static $instance;
        return $instance ??= new self();
    }

}