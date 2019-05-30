<?php

namespace App\Pagination\Adapter;

use App\Pagination\AdapterResult;
use App\Pagination\PageInterface;

/**
 * Pagination adapter that takes an array of entries and ignores groups and page
 * data, assuming this has been handled elsewhere.
 */
final class ArrayAdapter implements AdapterInterface {
    /**
     * @var array
     */
    private $entries;

    public function __construct(array $entries) {
        $this->entries = $entries;
    }

    public function getResults(int $maxPerPage, string $group, PageInterface $page): AdapterResult {
        $entries = $this->entries;

        if (\count($entries) >= $maxPerPage) {
            $pagerEntity = $entries[$maxPerPage];

            $entries = \array_slice($entries, 0, $maxPerPage);
        }

        return new AdapterResult($entries, $pagerEntity ?? null);
    }
}
