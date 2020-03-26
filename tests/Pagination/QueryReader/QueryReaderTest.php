<?php

namespace App\Tests\Pagination\QueryReader;

use App\Pagination\QueryReader\QueryReader;
use App\Tests\Fixtures\Pagination\PagerDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Validator\Validation;

/**
 * @covers \App\Pagination\QueryReader\QueryReader
 */
class QueryReaderTest extends TestCase {
    /**
     * @var QueryReader
     */
    private $reader;

    /**
     * @var RequestStack
     */
    private $requestStack;

    protected function setUp(): void {
        $denormalizer = new ObjectNormalizer();
        $validator = Validation::createValidatorBuilder()
            ->enableAnnotationMapping()
            ->getValidator();

        $this->requestStack = new RequestStack();
        $this->reader = new QueryReader($denormalizer, $this->requestStack, $validator);
    }

    public function testValidQueryYieldsPageObject(): void {
        $this->requestStack->push(new Request([
            'next' => [
                'ranking' => '69',
                'id' => '420',
            ],
        ]));

        $page = $this->reader->getFromRequest(PagerDTO::class, 'pager');

        $this->assertInstanceOf(PagerDTO::class, $page);
        $this->assertSame('69', $page->ranking);
        $this->assertSame('420', $page->id);
    }

    public function testInvalidQueryYieldsNull(): void {
        $this->requestStack->push(new Request([
            'next' => [
                'id' => '420',
            ],
        ]));

        $page = $this->reader->getFromRequest(PagerDTO::class, 'pager');

        $this->assertNull($page);
    }

    public function testMissingQueryYieldsNull(): void {
        $this->requestStack->push(new Request());

        $page = $this->reader->getFromRequest(PagerDTO::class, 'pager');

        $this->assertNull($page);
    }

    public function testEmptyRequestStackYieldsNull(): void {
        $page = $this->reader->getFromRequest(PagerDTO::class, 'pager');

        $this->assertNull($page);
    }
}
