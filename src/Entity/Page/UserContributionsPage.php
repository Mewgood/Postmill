<?php

namespace App\Entity\Page;

use App\Entity\Comment;
use App\Entity\Submission;
use App\Pagination\PageInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class UserContributionsPage implements PageInterface {
    /**
     * @Assert\NotBlank(groups={"pager"})
     * @Assert\DateTime(format=\DateTimeInterface::RFC3339, groups={"pager"})
     *
     * @Groups({"pager"})
     */
    public $timestamp;

    public function populateFromPagerEntity($entity): void {
        if (!$entity instanceof Comment && !$entity instanceof Submission) {
            throw new \InvalidArgumentException(sprintf(
                '$entity must be instance of %s or %s',
                Comment::class,
                Submission::class
            ));
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
