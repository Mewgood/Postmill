<?php

namespace App\Tests\Entity;

use App\Entity\CustomTextFlair;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\CustomTextFlair
 */
class CustomTextFlairTest extends TestCase {
    private function flair(): CustomTextFlair {
        return new CustomTextFlair('some text');
    }

    public function testGetLabel(): void {
        $this->assertSame('some text', $this->flair()->getLabel());
    }

    public function testGetType(): void {
        $this->assertSame('custom_text', $this->flair()->getType());
    }

    public function testCannotCreateWithEmptyString(): void {
        $this->expectException(\InvalidArgumentException::class);

        new CustomTextFlair('');
    }
}
