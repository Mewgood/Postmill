<?php

namespace App\Pagination\DTO;

use App\Entity\Submission;
use App\Pagination\PageInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class SubmissionPage implements PageInterface {
    public const SORT_FIELD_MAP = [
        Submission::SORT_HOT => ['ranking', 'id'],
        Submission::SORT_NEW => ['id'],
        Submission::SORT_ACTIVE => ['lastActive', 'id'],
        Submission::SORT_TOP => ['netScore', 'id'],
        Submission::SORT_CONTROVERSIAL => ['netScore', 'id'],
        Submission::SORT_MOST_COMMENTED => ['commentCount', 'id'],
    ];

    public const SORT_ORDER = [
        Submission::SORT_HOT            => PageInterface::SORT_DESC,
        Submission::SORT_NEW            => PageInterface::SORT_DESC,
        Submission::SORT_ACTIVE         => PageInterface::SORT_DESC,
        Submission::SORT_TOP            => PageInterface::SORT_DESC,
        Submission::SORT_CONTROVERSIAL  => PageInterface::SORT_ASC,
        Submission::SORT_MOST_COMMENTED => PageInterface::SORT_DESC,
    ];

    /**
     * @Assert\NotBlank(groups={"hot", "new", "active", "top", "controversial", "most_commented"})
     * @Assert\Range(min=1, groups={"hot", "new", "active", "top", "controversial", "most_commented"})
     *
     * @Groups({"hot", "new", "active", "top", "controversial", "most_commented"})
     */
    public $id;

    /**
     * @Assert\NotBlank(groups={"hot"})
     *
     * @Groups({"hot"})
     */
    public $ranking;

    /**
     * @Assert\NotBlank(groups={"active"})
     * @Assert\DateTime(format=\DateTime::RFC3339, groups={"active"})
     *
     * @Groups({"active"})
     */
    public $lastActive;

    /**
     * @Assert\NotBlank(groups={"top", "controversial"})
     * @Assert\Range(min=-2147483648, max=2147483647, groups={"top", "controversial"})
     *
     * @Groups({"top", "controversial"})
     */
    public $netScore;

    /**
     * @Assert\NotBlank(groups={"most_commented"})
     * @Assert\Range(min=0, max=2147483647, groups={"most_commented"})
     *
     * @Groups({"most_commented"})
     */
    public $commentCount;

    public function getPaginationFields(string $group): array {
        if (!isset(self::SORT_FIELD_MAP[$group])) {
            throw new \InvalidArgumentException("Unknown group '$group'");
        }

        return self::SORT_FIELD_MAP[$group];
    }

    public function getSortOrder(string $group): string {
        if (!isset(self::SORT_ORDER[$group])) {
            throw new \InvalidArgumentException("Unknown group '$group'");
        }

        return self::SORT_ORDER[$group];
    }

    public function populateFromPagerEntity($entity): void {
        if (!$entity instanceof Submission) {
            throw new \InvalidArgumentException(
                '$entity must be instance of '.Submission::class
            );
        }

        $this->id = $entity->getId();
        $this->ranking = $entity->getRanking();
        $this->lastActive = $entity->getLastActive();
        $this->netScore = $entity->getNetScore();
        $this->commentCount = $entity->getCommentCount();
    }
}
