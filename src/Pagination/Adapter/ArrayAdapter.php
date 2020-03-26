<?php

namespace App\Pagination\Adapter;

use App\Pagination\AdapterResult;
use App\Pagination\PageInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class ArrayAdapter implements AdapterInterface {
    /**
     * @var array
     */
    private $entries;

    private $filteringSkipped = false;

    public function __construct(array $entries) {
        $this->entries = $entries;
    }

    public function getResults(int $maxPerPage, string $group, PageInterface $page): AdapterResult {
        $accessor = PropertyAccess::createPropertyAccessor();
        $desc = $page->getSortOrder($group) === PageInterface::SORT_DESC;
        $entries = $this->entries;
        $fields = $page->getPaginationFields($group);
        $query = [];

        foreach ($fields as $field) {
            $query[$field] = $accessor->getValue($page, $field);

            if ($query[$field] === null) {
                $query = [];
                break;
            }
        }

        if (!$this->filteringSkipped && \count($query) > 0) {
            $entries = array_filter($entries, static function ($entry) use ($accessor, $desc, $query, $page): bool {
                $data = clone $page;
                $data->populateFromPagerEntity($entry);
                $values = [];

                foreach ($query as $field => $queryValue) {
                    $values[$field] = $accessor->getValue($data, $field);
                }

                return $desc ? $values <= $query : $values >= $query;
            });
        }

        usort($entries, static function ($x, $y) use ($desc, $page, $fields, $accessor) {
            $aData = clone $page;
            $aData->populateFromPagerEntity($x);
            $bData = clone $page;
            $bData->populateFromPagerEntity($y);
            $a = $b = [];

            foreach ($fields as $field) {
                $a[] = $accessor->getValue($aData, $field);
                $b[] = $accessor->getValue($bData, $field);
            }

            return $desc ? $b <=> $a : $a <=> $b;
        });

        $entries = \array_slice($entries, 0, $maxPerPage + 1);
        $pagerEntry = isset($entries[$maxPerPage]) ? array_pop($entries) : null;

        return new AdapterResult($entries, $pagerEntry);
    }

    public function withFilteringSkipped(): self {
        $self = clone $this;
        $self->filteringSkipped = true;

        return $self;
    }
}
