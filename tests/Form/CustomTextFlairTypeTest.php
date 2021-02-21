<?php

namespace App\Tests\Form;

use App\DataObject\CustomTextFlairData;
use App\Form\CustomTextFlairType;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * @covers \App\Form\CustomTextFlairType
 */
class CustomTextFlairTypeTest extends TypeTestCase {
    public function testSubmit(): void {
        $form = $this->factory->create(CustomTextFlairType::class);
        $form->submit([
            'text' => 'bear appreciation thread',
        ]);

        $this->assertCount(0, $form->getErrors());
        $this->assertInstanceOf(CustomTextFlairData::class, $form->getData());
        $this->assertSame('bear appreciation thread', $form->getData()->getText());
    }
}
