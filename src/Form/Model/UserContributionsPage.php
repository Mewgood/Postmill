<?php

namespace App\Form\Model;

use App\Entity\Comment;
use App\Entity\Submission;
use Symfony\Component\Validator\Constraints as Assert;

class UserContributionsPage {
    /**
     * @Assert\NotBlank()
     * @Assert\DateTime(format=\DateTimeInterface::RFC3339)
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
}
