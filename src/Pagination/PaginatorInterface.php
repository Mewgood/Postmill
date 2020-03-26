<?php

namespace App\Pagination;

use App\Pagination\Adapter\AdapterInterface;

interface PaginatorInterface {
    public function paginate(
        AdapterInterface $adapter,
        int $maxPerPage,
        string $pageDataClass,
        string $group = 'pager'
    ): Pager;
}
