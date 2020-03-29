<?php

namespace App\Entity\Page;

use App\Pagination\PageInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class TimestampPage implements PageInterface {
    /**
     * @Assert\NotBlank(groups={"pager"})
     * @Assert\DateTime(format=\DateTimeInterface::RFC3339, groups={"pager"})
     *
     * @Groups({"pager"})
     */
    public $timestamp;

    public function populateFromPagerEntity($entity): void {
        if (!\is_object($entity) || !\is_callable([$entity, 'getTimestamp'])) {
            throw new \TypeError('$entity must be object with getTimestamp() method');
        }

        $this->timestamp = $entity->getTimestamp();
    }

    public function getPaginationFields(string $group): array {
        return ['timestamp'];
    }

    public function getSortOrder(string $group): string {
        return PageInterface::SORT_DESC;
    }
}
