<?php

namespace App\Pagination;

/**
 * Contract for classes that define how to retrieve a page in a pagination
 * setting.
 */
interface PageInterface {
    /**
     * Get the fields of the entity/DTO used for pagination.
     *
     * @return string[]
     */
    public function getPaginationFields(): array;
}
