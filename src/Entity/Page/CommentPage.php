<?php

namespace App\Entity\Page;

use App\Entity\Comment;
use App\Pagination\PageInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class CommentPage implements PageInterface {
    /**
     * @Assert\Range(min=1, max=PHP_INT_MAX, groups={"pager"})
     * @Assert\NotBlank(groups={"pager"})
     *
     * @Groups("pager")
     *
     * @var string
     */
    public $id;

    /**
     * @Assert\DateTime(format=\DateTimeInterface::RFC3339, groups={"pager"})
     * @Assert\NotBlank(groups={"pager"})
     *
     * @Groups("pager")
     *
     * @var string
     */
    public $timestamp;

    public function getPaginationFields(string $group): array {
        return ['timestamp', 'id'];
    }

    public function getSortOrder(string $group): string {
        return self::SORT_DESC;
    }

    public function populateFromPagerEntity($entity): void {
        if (!$entity instanceof Comment) {
            throw new \InvalidArgumentException('$entity must be instance of '.Comment::class);
        }

        $this->id = (string) $entity->getId();
        $this->timestamp = $entity->getTimestamp()->format(\DateTimeInterface::RFC3339);
    }
}
