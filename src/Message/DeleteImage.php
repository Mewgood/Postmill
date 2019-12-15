<?php

namespace App\Message;

class DeleteImage {
    /**
     * @var string[]
     */
    private $images;

    public function __construct(string ...$images) {
        $this->images = $images;
    }

    public function getImages(): array {
        return $this->images;
    }
}
