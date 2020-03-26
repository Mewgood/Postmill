<?php

namespace App\Tests\Pagination;

use App\Pagination\Adapter\AdapterInterface;
use App\Pagination\AdapterResult;
use App\Pagination\PageInterface;
use App\Pagination\Paginator;
use App\Pagination\QueryReader\QueryReaderInterface;
use App\Tests\Fixtures\Pagination\Entity;
use App\Tests\Fixtures\Pagination\PagerDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * @covers \App\Pagination\Paginator
 */
class PaginatorTest extends TestCase {
    /**
     * @var AdapterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $adapter;

    /**
     * @var QueryReaderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $queryReader;

    /**
     * @var Paginator
     */
    private $paginator;

    protected function setUp(): void {
        $this->adapter = $this->createMock(AdapterInterface::class);
        $this->queryReader = $this->createMock(QueryReaderInterface::class);
        $this->paginator = new Paginator(new ObjectNormalizer(), $this->queryReader);
    }

    public function testPaginatorWithEmptyQueryAndNextPage(): void {
        $this->queryReader
            ->expects($this->once())
            ->method('getFromRequest')
            ->willReturn(null);

        $this->adapter
            ->expects($this->once())
            ->method('getResults')
            ->with(
                $this->identicalTo(4),
                $this->identicalTo('pager'),
                $this->isInstanceOf(PageInterface::class)
            )
            ->willReturn(new AdapterResult([
                new Entity(12, 6),
                new Entity(11, 5),
                new Entity(10, 4),
                new Entity(9, 3),
            ], new Entity(8, 2)));

        $pager = $this->paginator->paginate($this->adapter, 4, PagerDTO::class);

        $this->assertSame([6, 5, 4, 3], array_column(iterator_to_array($pager), 'id'));
        $this->assertSame(
            ['next' => ['ranking' => 8, 'id' => 2]],
            $pager->getNextPageParams()
        );
    }

    public function testPaginatorWithQueryAndEmptyNextPage(): void {
        $page = new PagerDTO();
        $page->ranking = 5;
        $page->id = 4;

        $this->queryReader
            ->expects($this->once())
            ->method('getFromRequest')
            ->willReturn($page);

        $this->adapter
            ->expects($this->once())
            ->method('getResults')
            ->willReturn(new AdapterResult([
                new Entity(5, 4),
                new Entity(4, 3),
            ], null));

        $pager = $this->paginator->paginate($this->adapter, 4, PagerDTO::class);

        $this->assertSame([4, 3], array_column(iterator_to_array($pager), 'id'));
    }
}
