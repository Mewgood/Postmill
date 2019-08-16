<?php

namespace App\Tests\Flysystem;

use App\Flysystem\SubmissionImageManager;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SubmissionImageManagerTest extends TestCase {
    /**
     * @var FilesystemInterface|MockObject
     */
    private $filesystem;

    /**
     * @var SubmissionImageManager
     */
    private $manager;

    protected function setUp() {
        $this->filesystem = $this->createMock(FilesystemInterface::class);

        $this->manager = new SubmissionImageManager($this->filesystem);
    }

    public function testCanGuessFilenameOfPngImage(): void {
        $this->assertSame(
            'a91d6c2201d32b8c39bff1143a5b29e74b740248c5d65810ddcbfa16228d49e9.png',
            $this->manager->getFileName(__DIR__.'/../Resources/120px-12-Color-SVG.svg.png')
        );
    }

    public function testCanStoreImage(): void {
        $this->filesystem
            ->expects($this->once())
            ->method('writeStream')
            ->with(
                $this->equalTo('destination.png'),
                $this->callback('is_resource')
            )
            ->willReturn(true);

        $this->manager->store(
            __DIR__.'/../Resources/120px-12-Color-SVG.svg.png',
            'destination.png'
        );
    }

    public function testStoreHandlesCollidingFileNames(): void {
        $this->filesystem
            ->expects($this->once())
            ->method('writeStream')
            ->with(
                $this->equalTo('destination.png'),
                $this->callback('is_resource')
            )
            ->willThrowException(new FileExistsException('destination.png'));

        $this->manager->store(
            __DIR__.'/../Resources/120px-12-Color-SVG.svg.png',
            'destination.png'
        );
    }

    public function testCanPruneImage(): void {
        $this->filesystem
            ->expects($this->once())
            ->method('delete')
            ->with('image.png');

        $this->manager->prune('image.png');
    }

    public function testPruneHandlesNonExistentFiles(): void {
        $this->filesystem
            ->expects($this->once())
            ->method('delete')
            ->with('image.png')
            ->willThrowException(new FileNotFoundException('image.png'));

        $this->manager->prune('image.png');
    }
}
