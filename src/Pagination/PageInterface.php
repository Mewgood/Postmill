<?php

namespace App\Pagination;

/**
 * Contract for classes that define how to retrieve a page in a pagination
 * setting.
 */
interface PageInterface {
    public const SORT_ASC = 'ASC';
    public const SORT_DESC = 'DESC';

    /**
     * Get the fields of the entity/DTO used for pagination.
     *
     *
     * @return string[]
     */
    public function getPaginationFields(string $group): array;

    /**
     * @return string one of `SORT_*` constants
     */
    public function getSortOrder(string $group): string;

    /**
     * Use the `(max_per_page + n)`th entity used for pagination to populate the
     * page object's fields.
     *
     * @param object|mixed $entity
     */
    public function populateFromPagerEntity($entity): void;
}
