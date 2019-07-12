<?php

namespace App\Message;

class DeleteSubmissionImage {
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
