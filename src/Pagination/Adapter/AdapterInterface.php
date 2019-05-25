<?php

namespace App\Pagination\Adapter;

use App\Pagination\AdapterResult;
use App\Pagination\PageInterface;

interface AdapterInterface {
    public function getResults(int $maxPerPage, string $group, PageInterface $page): AdapterResult;
}
