<?php

namespace App\Pagination\Adapter;

use App\Pagination\AdapterResult;
use App\Pagination\PageInterface;

final class UnionAdapter implements AdapterInterface {
    /**
     * @var AdapterInterface[]
     */
    private $adapters;

    public function __construct(AdapterInterface $adapter, AdapterInterface ...$adapters) {
        $this->adapters = array_merge([$adapter], $adapters);
    }

    public function getResults(int $maxPerPage, string $group, PageInterface $page): AdapterResult {
        $results = [];

        foreach ($this->adapters as $adapter) {
            $results[] = $adapter->getResults($maxPerPage, $group, $page)->getEntries();
        }

        $results = array_merge(...$results);

        return (new ArrayAdapter($results))
            ->withFilteringSkipped()
            ->getResults($maxPerPage, $group, $page);
    }
}
