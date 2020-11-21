<?php

namespace App\Tests\Flysystem;

use App\Flysystem\ImageManager;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\MimeTypesInterface;

/**
 * @covers \App\Flysystem\ImageManager
 */
class ImageManagerTest extends TestCase {
    /**
     * @var FilesystemInterface|MockObject
     */
    private $filesystem;

    /**
     * @var ImageManager
     */
    private $manager;

    /**
     * @var MimeTypesInterface
     */
    private $mimeTypeGuesser;

    protected function setUp(): void {
        $this->filesystem = $this->createMock(FilesystemInterface::class);
        $this->mimeTypeGuesser = $this->createMock(MimeTypesInterface::class);

        $this->manager = new ImageManager($this->filesystem, $this->mimeTypeGuesser);
    }

    public function testCanGuessFilenameOfPngImage(): void {
        $this->mimeTypeGuesser
            ->expects($this->once())
            ->method('guessMimeType')
            ->with(__DIR__.'/../Resources/120px-12-Color-SVG.svg.png')
            ->willReturn('image/png');

        $this->mimeTypeGuesser
            ->expects($this->once())
            ->method('getExtensions')
            ->with('image/png')
            ->willReturn(['png', 'nope']);

        $this->assertSame(
            'a91d6c2201d32b8c39bff1143a5b29e74b740248c5d65810ddcbfa16228d49e9.png',
            $this->manager->getFileName(__DIR__.'/../Resources/120px-12-Color-SVG.svg.png')
        );
    }

    public function testThrowsIfMimeTypeCannotBeGuessed(): void {
        $this->expectException(\RuntimeException::class);

        $this->mimeTypeGuesser
            ->expects($this->once())
            ->method('guessMimeType')
            ->with(__DIR__.'/../Resources/120px-12-Color-SVG.svg.png')
            ->willReturn(null);

        $this->mimeTypeGuesser
            ->expects($this->never())
            ->method('getExtensions');

        $this->manager->getFileName(__DIR__.'/../Resources/120px-12-Color-SVG.svg.png');
    }

    public function testThrowsIfNoExtensionForMimeType(): void {
        $this->expectException(\RuntimeException::class);

        $this->mimeTypeGuesser
            ->expects($this->once())
            ->method('guessMimeType')
            ->with(__DIR__.'/../Resources/120px-12-Color-SVG.svg.png')
            ->willReturn('image/png');

        $this->mimeTypeGuesser
            ->expects($this->once())
            ->method('getExtensions')
            ->with('image/png')
            ->willReturn([]);

        $this->manager->getFileName(__DIR__.'/../Resources/120px-12-Color-SVG.svg.png');
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
