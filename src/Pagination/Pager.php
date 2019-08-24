<?php

namespace App\Pagination;

class Pager implements \Countable, \IteratorAggregate {
    /**
     * @var array
     */
    private $entries;

    /**
     * @var array
     */
    private $params;

    public function __construct(array $entries, array $nextPageParams = []) {
        $this->entries = $entries;
        $this->params['next'] = $nextPageParams;
    }

    public function getIterator(): \Iterator {
        return new \ArrayIterator($this->entries);
    }

    public function hasNextPage(): bool {
        return !empty($this->params['next']);
    }

    /**
     * @throws \BadMethodCallException if there is no next page
     */
    public function getNextPageParams(): array {
        if (!$this->hasNextPage()) {
            throw new \BadMethodCallException('There is no next page');
        }

        return ['next' => $this->params['next']];
    }

    public function isEmpty(): bool {
        return empty($this->entries);
    }

    public function count(): int {
        return \count($this->entries);
    }
}
