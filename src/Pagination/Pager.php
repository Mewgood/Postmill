<?php

namespace App\Pagination;

class Pager implements \IteratorAggregate {
    /**
     * @var array
     */
    private $entries;

    /**
     * @var array
     */
    private $nextParams;

    public function __construct(array $entries, array $nextParams) {
        $this->entries = $entries;
        $this->nextParams = $nextParams;
    }

    public function getIterator(): \Iterator {
        return new \ArrayIterator($this->entries);
    }

    public function hasNextPage(): bool {
        return !empty($this->nextParams);
    }

    /**
     * @throws \BadMethodCallException if there is no next page
     */
    public function getNextPageParams(): array {
        if (!$this->hasNextPage()) {
            throw new \BadMethodCallException('There is no next page');
        }

        return $this->nextParams;
    }

    public function isEmpty(): bool {
        return empty($this->entries);
    }
}
