<?php

namespace App\Pagination\Adapter;

use App\Pagination\AdapterResult;
use App\Pagination\PageInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Paginate a Doctrine ORM `QueryBuilder`.
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
                $elements[] = ["{$alias}.{$field}", ":next_{$field}"];
                $qb->setParameter("next_{$field}", $accessor->getValue($page, $field));
            }

            $qb->addOrderBy("{$alias}.{$field}", $page->getSortOrder($group));
        }

        if (\count($elements) > 0) {
            $desc = $page->getSortOrder($group) === PageInterface::SORT_DESC;
            $this->mangleQuery($qb, $elements, $desc);
        }

        $results = $qb->getQuery()->execute();
        $pagerEntity = \count($results) > $maxPerPage ? array_pop($results) : null;

        return new AdapterResult($results, $pagerEntity);
    }

    /**
     * This simulates row constructor/tuple comparison, which isn't available
     * in Doctrine ORM.
     *
     * ~~~
     * (a, b, c) <= (3, 4, 5)
     * becomes
     * (a <= 3) AND (a = 3 OR b <= 4) AND (a = 3 AND b = 4 OR c <= 5)
     * ~~~
     *
     * @param string[] $elements
     */
    private function mangleQuery(QueryBuilder $qb, array $elements, bool $desc): void
    {
        $cmp = $desc ? 'lte' : 'gte';
        $i = 0;

        $expr = $qb->expr()->andX(...array_map(static function ($field) use ($cmp, $elements, $qb, &$i) {
            $expr[] = $qb->expr()->andX(...array_map(static function ($field) use ($qb) {
                return $qb->expr()->eq($field[0], $field[1]);
            }, array_slice($elements, 0, $i++)));

            $expr[] = $qb->expr()->{$cmp}($field[0], $field[1]);

            return $qb->expr()->orX(...$expr);
        }, $elements));

        $qb->andWhere($expr);
    }
}
