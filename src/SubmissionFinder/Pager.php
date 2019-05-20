<?php

namespace App\SubmissionFinder;

use App\Entity\Submission;

class Pager implements \IteratorAggregate {
    /**
     * @var Submission[]
     */
    private $submissions = [];

    private $nextParams = [];

    public function __construct(array $submissions, array $nextParams) {
        $this->submissions = $submissions;
        $this->nextParams = $nextParams;
    }

    public function getIterator() {
        return new \ArrayIterator($this->submissions);
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
        return empty($this->submissions);
    }
}
