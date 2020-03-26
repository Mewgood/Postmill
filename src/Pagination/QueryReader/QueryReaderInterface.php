<?php

namespace App\Pagination\QueryReader;

use App\Pagination\PageInterface;

interface QueryReaderInterface {
    public function getFromRequest(string $pageDataClass, string $group): ?PageInterface;
}
