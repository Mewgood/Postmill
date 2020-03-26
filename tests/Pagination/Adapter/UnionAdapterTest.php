<?php

namespace App\Tests\Pagination\Adapter;

use App\Pagination\Adapter\ArrayAdapter;
use App\Pagination\Adapter\UnionAdapter;
use App\Tests\Fixtures\Pagination\Entity;
use App\Tests\Fixtures\Pagination\PagerDTO;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Pagination\Adapter\UnionAdapter
 */
class UnionAdapterTest extends TestCase {
    public function testPaging(): void {
        $adapter = new UnionAdapter(
            new ArrayAdapter([
                new Entity(4, 2),
                new Entity(4, 3),
                new Entity(7, 4),
            ]),
            new ArrayAdapter([
                new Entity(4, 5),
                new Entity(10, 1),
                new Entity(6, 6),
                new Entity(2, 100),
            ])
        );

        $adapterResult = $adapter->getResults(5, 'pager', new PagerDTO());
        $entries = $adapterResult->getEntries();

        $this->assertSame([1, 4, 6, 5, 3], array_column($entries, 'id'));
        $this->assertSame(2, $adapterResult->getPagerEntity()->id);
    }

}
