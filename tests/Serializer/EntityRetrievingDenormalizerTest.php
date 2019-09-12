<?php

namespace App\Tests\Serializer;

use App\Serializer\EntityRetrievingDenormalizer;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class EntityRetrievingDenormalizerTest extends TestCase {
    /**
     * @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $entityManager;

    /**
     * @var EntityRetrievingDenormalizer
     */
    private $normalizer;

    public function setUp(): void {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->normalizer = new EntityRetrievingDenormalizer($this->entityManager);
    }

    /**
     * @dataProvider entityProvider
     */
    public function testSupportsEntities($id, $type): void {
        $this->assertTrue($this->normalizer->supportsDenormalization($id, $type));
    }

    /**
     * @dataProvider invalidEntityProvider
     */
    public function testDoesNotSupportInvalidDataAndType($id, $type) {
        $this->assertFalse($this->normalizer->supportsDenormalization($id, $type));
    }

    /**
     * @dataProvider entityProvider
     */
    public function testCanDenormalizeEntities($id, $type): void {
        $this->entityManager
            ->expects($this->once())
            ->method('find')
            ->with($this->equalTo($type), $this->equalTo($id))
            ->willReturn($this->createMock($type));

        $denormalized = $this->normalizer->denormalize($id, $type);

        $this->assertInstanceOf($type, $denormalized);
    }

    public function entityProvider(): iterable {
        yield [1, 'App\Entity\Comment'];
        yield [2, 'App\Entity\Forum'];
        yield ['12341234-1234-1234-1234-123412341234', 'App\Entity\Message'];
    }

    public function invalidEntityProvider(): iterable {
        yield [1, 'App\Entity\Exception\BannedFromForumException'];
        yield [null, 'App\Entity\Forum'];
        yield [[], 'App\Entity\Forum'];
        yield [(object) [], 'App\Entity\Message'];
    }
}
