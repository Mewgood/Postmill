<?php

namespace App\Tests\DataObject;

use App\DataObject\CustomTextFlairData;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\DataObject\CustomTextFlairData
 */
class CustomTextFlairDataTest extends TestCase {
    private function data(): CustomTextFlairData {
        return new CustomTextFlairData();
    }

    public function testGetText(): void {
        $this->assertNull($this->data()->getText());
    }

    public function testSetText(): void {
        $data = $this->data();

        $data->setText('some text');

        $this->assertSame('some text', $data->getText());
    }
}
