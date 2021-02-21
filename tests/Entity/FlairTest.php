<?php

namespace App\Tests\Entity;

use App\Entity\Flair;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Rfc4122\FieldsInterface;

/**
 * @covers \App\Entity\Flair
 */
class FlairTest extends TestCase {
    private function flair(): Flair {
        return $this->getMockForAbstractClass(Flair::class);
    }

    /**
     * @testdox ID is UUIDv4
     */
    public function testIdIsUuidV4(): void {
        $fields = $this->flair()->getId()->getFields();

        $this->assertInstanceOf(FieldsInterface::class, $fields);
        $this->assertSame(4, $fields->getVersion());
    }
}
