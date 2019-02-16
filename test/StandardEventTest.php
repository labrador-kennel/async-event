<?php declare(strict_types=1);

namespace Cspray\Labrador\AsyncEvent\Test;

use Cspray\Labrador\AsyncEvent\StandardEvent;
use PHPUnit\Framework\TestCase;

class StandardEventTest extends TestCase {

    public function testCreatesDateTimeOnConstruct() {
        $actual = new \DateTimeImmutable();
        $subject = new StandardEvent('labrador.test', new \stdClass());
        $createdAt = $subject->createdAt();
        $diff = $actual->diff($createdAt);
        $this->assertEmpty($diff->s);
        $this->assertNotEmpty($diff->f);
    }
}
