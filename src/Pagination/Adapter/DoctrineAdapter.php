<?php

namespace App\Pagination\Adapter;

use App\Pagination\AdapterResult;
use App\Pagination\PageInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Paginate a Doctrine ORM `QueryBuilder`.
 *
 * If more than one query builder is provided, the fields used for pagination
 * must match in every builder.
 */
final class DoctrineAdapter implements AdapterInterface {
    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    public function __construct(QueryBuilder $queryBuilder) {
        $this->queryBuilder = $queryBuilder;
    }

    public function getResults(int $maxPerPage, string $group, PageInterface $page): AdapterResult {
        $qb = clone $this->queryBuilder;
        $qb->setMaxResults($maxPerPage + 1);

        // TODO: how do we handle cases where the field isn't on the root entity?
        $alias = $qb->getRootAliases()[0];
        $elements = [];
        $accessor = PropertyAccess::createPropertyAccessor();

        foreach ($page->getPaginationFields($group) as $field) {
            if ($page->{$field} !== null) {
                $elements["{$alias}.{$field}"] = ":next_{$field}";
                $qb->setParameter("next_{$field}", $accessor->getValue($page, $field));
            }

            $qb->addOrderBy("{$alias}.{$field}", $page->getSortOrder($group));
        }

        if (\count($elements) > 0) {
            $columns = sprintf('TUPLE(%s)', implode(', ', array_keys($elements)));
            $params = sprintf('TUPLE(%s)', implode(', ', $elements));

            if ($page->getSortOrder($group) === PageInterface::SORT_DESC) {
                $expr = $qb->expr()->lte($columns, $params);
            } else {
                $expr = $qb->expr()->gte($columns, $params);
            }

            $qb->andWhere($expr);
        }

        $results = $qb->getQuery()->execute();
        $pagerEntity = \count($results) > $maxPerPage ? array_pop($results) : null;

        return new AdapterResult($results, $pagerEntity);
    }
}
