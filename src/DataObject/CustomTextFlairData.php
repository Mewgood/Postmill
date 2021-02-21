<?php

namespace App\DataObject;

use Symfony\Component\Validator\Constraints as Assert;

class CustomTextFlairData {
    /**
     * @Assert\Length(max=35)
     * @Assert\NotBlank()
     *
     * @var string|null
     */
    private $text;

    public function getText(): ?string {
        return $this->text;
    }

    public function setText(?string $text): void {
        $this->text = $text;
    }
}
