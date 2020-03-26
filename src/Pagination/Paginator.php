<?php

namespace App\Pagination;

use App\Pagination\Adapter\AdapterInterface;
use App\Pagination\QueryReader\QueryReaderInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class Paginator implements PaginatorInterface {
    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    /**
     * @var QueryReaderInterface
     */
    private $queryReader;

    public function __construct(
        NormalizerInterface $normalizer,
        QueryReaderInterface $queryReader
    ) {
        $this->normalizer = $normalizer;
        $this->queryReader = $queryReader;
    }

    public function paginate(
        AdapterInterface $adapter,
        int $maxPerPage,
        string $pageDataClass,
        string $group = 'pager'
    ): Pager {
        $page = $this->queryReader->getFromRequest($pageDataClass, $group) ??
            new $pageDataClass();

        $result = $adapter->getResults($maxPerPage, $group, $page);
        $pagerEntity = $result->getPagerEntity();

        if ($pagerEntity) {
            $page->populateFromPagerEntity($pagerEntity);

            $nextPageParams = $this->normalizer->normalize($page, null, [
                'groups' => [$group],
            ]);
        }

        return new Pager($result->getEntries(), $nextPageParams ?? []);
    }
}
