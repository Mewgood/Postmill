<?php

namespace App\Pagination\Adapter;

use App\Pagination\AdapterResult;
use App\Pagination\PageInterface;
use App\Pagination\Pager;
use Doctrine\ORM\QueryBuilder;

/**
 * Combine the results of multiple Doctrine ORM query builders.
 *
 * This assumes all the entity types use equivalent fields for ordering.
 */
final class DoctrineUnionAdapter implements AdapterInterface {
    /**
     * @var QueryBuilder[]
     */
    private $queryBuilders;

    public function __construct(QueryBuilder ...$queryBuilders) {
        $this->queryBuilders = $queryBuilders;
    }

    public function getResults(int $maxPerPage, string $group, PageInterface $page): AdapterResult {
        $fields = $page->getPaginationFields($group);

        if (\count($fields) > 1) {
            // TODO: implement keyset pagination in doctrine DQL somehow
            throw new \LogicException('not implemented for more than one field');
        }

        $sortOrder = $page->getSortOrder($group);
        $results = [];

        foreach ($this->queryBuilders as $qb) {
            $qb = clone $qb;
            $qb->setMaxResults($maxPerPage + 1);

            // TODO: how do we handle cases where the field isn't on the root
            // entity???
            $alias = $qb->getRootAlias();

            foreach ($fields as $field) {
                if ($sortOrder === PageInterface::SORT_DESC) {
                    $expr = $qb->expr()->lte("$alias.$field", ":next_{$field}");
                } else {
                    $expr = $qb->expr()->gte("$alias.$field", ":next_{$field}");
                }

                if ($page->{$field} !== null) {
                    $qb->andWhere($expr);
                    $qb->setParameter('next_'.$field, $page->{$field});
                }

                $qb->addOrderBy("$alias.$field", $sortOrder);
            }

            $results = \array_merge($results, $qb->getQuery()->execute());
        }

        \usort($results, function ($x, $y) use ($fields, $page, $sortOrder) {
            $aData = clone $page;
            $aData->populateFromPagerEntity($x);
            $bData = clone $page;
            $bData->populateFromPagerEntity($y);
            $a = $b = [];

            foreach ($fields as $field) {
                $a[] = $aData->{$field};
                $b[] = $bData->{$field};
            }

            return $sortOrder === PageInterface::SORT_DESC
                ? $b <=> $a
                : $a <=> $b;
        });

        $pagerEntity = $results[$maxPerPage] ?? null;
        $results = \array_slice($results, 0, $maxPerPage);

        return new AdapterResult($results, $pagerEntity);
    }
}
