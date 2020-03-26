<?php

namespace App\Tests\Fixtures\Pagination;

class Entity {
    public $ranking;
    public $id;

    public function __construct(int $ranking, int $id) {
        $this->ranking = $ranking;
        $this->id = $id;
    }
}
