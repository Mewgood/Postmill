<?php

namespace App\Pagination\Adapter;

use App\Pagination\AdapterResult;
use App\Pagination\PageInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class UnionAdapter implements AdapterInterface {
    /**
     * @var AdapterInterface[]
     */
    private $adapters;

    public function __construct(AdapterInterface ...$adapters) {
        if (\count($adapters) === 0) {
            throw new \InvalidArgumentException('At least one adapter must be specified');
        }

        $this->adapters = $adapters;
    }

    public function getResults(int $maxPerPage, string $group, PageInterface $page): AdapterResult {
        $results = [];

        foreach ($this->adapters as $adapter) {
            $results[] = $adapter->getResults($maxPerPage, $group, $page)->getEntries();
        }

        $results = array_merge(...$results);
        $accessor = PropertyAccess::createPropertyAccessor();

        usort($results, function ($x, $y) use ($group, $page, $accessor) {
            $aData = clone $page;
            $aData->populateFromPagerEntity($x);
            $bData = clone $page;
            $bData->populateFromPagerEntity($y);
            $a = $b = [];

            foreach ($page->getPaginationFields($group) as $field) {
                $a[] = $accessor->getValue($aData, $field);
                $b[] = $accessor->getValue($bData, $field);
            }

            return $page->getSortOrder($group) === PageInterface::SORT_DESC
                ? $b <=> $a
                : $a <=> $b;
        });

        $pagerEntity = $results[$maxPerPage] ?? null;
        $results = \array_slice($results, 0, $maxPerPage);

        return new AdapterResult($results, $pagerEntity);
    }
}
