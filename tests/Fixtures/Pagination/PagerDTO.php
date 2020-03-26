<?php

namespace App\Tests\Fixtures\Pagination;

use App\Pagination\PageInterface;
use Symfony\Component\Validator\Constraints as Assert;

class PagerDTO implements PageInterface {
    /**
     * @Assert\NotBlank(groups={"pager"})
     */
    public $ranking;

    /**
     * @Assert\NotBlank(groups={"pager", "latest"})
     */
    public $id;

    public function getPaginationFields(string $group): array {
        if ($group === 'pager') {
            return ['ranking', 'id'];
        }

        if ($group === 'latest') {
            return ['id'];
        }

        throw new \InvalidArgumentException('Unknown group');
    }

    public function getSortOrder(string $group): string {
        return PageInterface::SORT_DESC;
    }

    public function populateFromPagerEntity($entity): void {
        $this->id = $entity->id;
        $this->ranking = $entity->ranking;
    }
}
