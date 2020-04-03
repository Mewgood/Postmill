<?php

namespace App\Pagination;

class AdapterResult {
    /**
     * @var array
     */
    private $entries;

    /**
     * @var object|mixed|null
     */
    private $pagerEntity;

    public function __construct(array $entries, $pagerEntity) {
        $this->entries = $entries;
        $this->pagerEntity = $pagerEntity;
    }

    public function getEntries(): array {
        return $this->entries;
    }

    /**
     * @return object|mixed|null
     */
    public function getPagerEntity() {
        return $this->pagerEntity;
    }
}
