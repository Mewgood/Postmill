<?php

namespace App\Pagination\DTO;

use App\Entity\Comment;
use App\Entity\Submission;
use App\Pagination\PageInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class UserContributionsPage implements PageInterface {
    /**
     * @Assert\NotBlank()
     * @Assert\DateTime(format=\DateTime::RFC3339)
     *
     * @Groups({"pager"})
     */
    public $timestamp;

    public static function createFromContribution($entity): self {
        if (!$entity instanceof Comment && !$entity instanceof Submission) {
            throw new \InvalidArgumentException(\sprintf(
                '$entity must be instance of %s or %s',
                Comment::class,
                Submission::class
            ));
        }

        $self = new self();
        $self->timestamp = $entity->getTimestamp();

        return $self;
    }

    public function getPaginationFields(): array {
        return ['timestamp'];
    }
}
