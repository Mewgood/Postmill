<?php

namespace App\Tests\Entity;

use App\Entity\Image;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\Image
 */
class ImageTest extends TestCase {
    public function testAcceptsFilename(): void {
        $image = new Image('a.png', 420, 69);
        $this->assertSame('a.png', $image->getFileName());
        $this->assertSame('a.png', (string) $image);
    }

    public function testConstructorWithDimensions(): void {
        $image = new Image('a.png', 420, 69);
        $this->assertSame(420, $image->getWidth());
        $this->assertSame(69, $image->getHeight());
    }

    public function testConstructorWithoutDimensions(): void {
        $image = new Image('a.png', null, null);
        $this->assertNull($image->getWidth());
        $this->assertNull($image->getHeight());
    }

    /**
     * @dataProvider provideInvalidDimensions
     */
    public function testConstructorDoesNotAcceptInvalidDimensions(?int $width, ?int $height): void {
        $this->expectException(\InvalidArgumentException::class);

        new Image('a.png', $width, $height);
    }

    /**
     * @dataProvider provideInvalidDimensions
     */
    public function testDimensionSetterDoesNotAcceptInvalidDimensions(?int $width, ?int $height): void {
        $this->expectException(\InvalidArgumentException::class);

        (new Image('a.png', null, null))->setDimensions($width, $height);
    }

    public function provideInvalidDimensions(): iterable {
        yield [420, null];
        yield [null, 69];
        yield [-420, null];
        yield [null, -69];
        yield [-420, -69];
    }
}
