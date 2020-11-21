<?php

namespace App\Tests\Repository;

use App\Entity\Image;
use App\Repository\ImageRepository;

/**
 * @covers \App\Repository\ImageRepository
 */
class ImageRepositoryTest extends RepositoryTestCase {
    private const FILE_NAME = __DIR__.'/../Resources/120px-12-Color-SVG.svg.png';
    private const SHA256 = 'a91d6c2201d32b8c39bff1143a5b29e74b740248c5d65810ddcbfa16228d49e9';
    private const WIDTH = 120;
    private const HEIGHT = 120;

    /**
     * @var ImageRepository
     */
    private $repository;

    protected function setUp(): void {
        parent::setUp();

        $this->repository = $this->entityManager->getRepository(Image::class);
    }

    public function testCreatesImageWhenNotKnown(): void {
        $image = $this->repository->findOrCreateFromPath(self::FILE_NAME);

        $this->assertFalse($this->entityManager->contains($image));
        $this->assertSame(self::SHA256, $image->getSha256());
        $this->assertSame(self::WIDTH, $image->getWidth());
        $this->assertSame(self::HEIGHT, $image->getHeight());
    }

    public function testGetsExistingImageWhenKnown(): void {
        $image = new Image('a.png', self::SHA256, 70, 90);
        $this->entityManager->persist($image);
        $this->entityManager->flush();

        $found = $this->repository->findOrCreateFromPath(self::FILE_NAME);

        $this->assertTrue($this->entityManager->contains($found));
        $this->assertSame($image, $found);
        $this->assertSame(70, $found->getWidth());
        $this->assertSame(90, $found->getHeight());
    }

    public function testGetsExistingImageAndFillsMissingDimensionsWhenKnown(): void {
        $image = new Image('a.png', self::SHA256, null, null);
        $this->entityManager->persist($image);
        $this->entityManager->flush();

        $found = $this->repository->findOrCreateFromPath(self::FILE_NAME);

        $this->assertTrue($this->entityManager->contains($found));
        $this->assertSame($image, $found);
        $this->assertSame(120, $found->getWidth());
        $this->assertSame(120, $found->getHeight());
    }
}
