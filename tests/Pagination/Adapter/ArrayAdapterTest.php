<?php

namespace App\Tests\Pagination\Adapter;

use App\Pagination\Adapter\ArrayAdapter;
use App\Tests\Fixtures\Pagination\Entity;
use App\Tests\Fixtures\Pagination\PagerDTO;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Pagination\Adapter\ArrayAdapter
 */
class ArrayAdapterTest extends TestCase {
    /**
     * @var ArrayAdapter
     */
    private $adapter;

    protected function setUp(): void {
        $this->adapter = new ArrayAdapter([
            new Entity(4, 2),
            new Entity(4, 3),
            new Entity(7, 4),
            new Entity(3, 11),
            new Entity(84, 6),
            new Entity(12, 12),
            new Entity(4, 1),
        ]);
    }

    public function testPaging(): void {
        $adapterResult = $this->adapter->getResults(5, 'pager', new PagerDTO());
        $entries = $adapterResult->getEntries();
        $pagerEntity = $adapterResult->getPagerEntity();

        $this->assertSame([6, 12, 4, 3, 2], array_column($entries, 'id'));
        $this->assertSame(1, $pagerEntity->id);
    }

    public function testPagingWithQuery(): void {
        $page = new PagerDTO();
        $page->ranking = 4;
        $page->id = 3;

        $entries = $this->adapter->getResults(2, 'pager', $page)->getEntries();
        $this->assertSame([3, 2], array_column($entries, 'id'));
    }

    public function testPagingWithQueryAndFilteringSkipped(): void {
        $page = new PagerDTO();
        $page->ranking = 3;
        $page->id = 2;

        $entries = $this->adapter
            ->withFilteringSkipped()
            ->getResults(2, 'pager', $page)->getEntries();
        $this->assertSame([6, 12], array_column($entries, 'id'));
    }
}


