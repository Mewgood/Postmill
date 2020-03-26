<?php

namespace App\Tests\Pagination;

use App\Pagination\AdapterResult;
use App\Tests\Fixtures\Pagination\Entity;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Pagination\AdapterResult
 */
class AdapterResultTest extends TestCase {
    public function testWithPopulatedResults(): void {
        $entries = [
            new Entity(4, 20),
            new Entity(4, 18),
            new Entity(6, 1),
            new Entity(1, 12),
        ];
        $pagerEntry = new Entity(0, 12);

        $adapterResult = new AdapterResult($entries, $pagerEntry);

        $this->assertSame($entries, $adapterResult->getEntries());
        $this->assertSame($pagerEntry, $adapterResult->getPagerEntity());
    }

    public function testWithEmptyResults(): void {
        $adapterResult = new AdapterResult([], null);

        $this->assertSame([], $adapterResult->getEntries());
        $this->assertNull($adapterResult->getPagerEntity());
    }
}
