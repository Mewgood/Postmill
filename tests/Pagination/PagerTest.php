<?php

namespace App\Tests\Pagination;

use App\Pagination\Pager;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Pagination\Pager
 */
class PagerTest extends TestCase {
    public function testEmptyPager(): void {
        $pager = new Pager([], []);

        $this->assertCount(0, $pager);
        $this->assertTrue($pager->isEmpty());
        $this->assertSame([], \iterator_to_array($pager));
        $this->assertFalse($pager->hasNextPage());

        $this->expectException(\BadMethodCallException::class);
        $pager->getNextPageParams();
    }

    public function testPopulatedPager(): void {
        $pager = new Pager([1, 2, 3], [4]);

        $this->assertCount(3, $pager);
        $this->assertFalse($pager->isEmpty());
        $this->assertSame([1, 2, 3], \iterator_to_array($pager));
        $this->assertTrue($pager->hasNextPage());
        $this->assertSame(['next' => [4]], $pager->getNextPageParams());
    }
}
