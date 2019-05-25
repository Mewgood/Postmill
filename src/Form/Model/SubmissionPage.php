<?php

namespace App\Form\Model;

use App\Entity\Submission;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class SubmissionPage {
    /**
     * @Assert\NotBlank(groups={"all"})
     * @Assert\Range(min=1, groups={"all"})
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

    public static function createFromSubmission(Submission $submission): self {
        $self = new self();
        $self->id = $submission->getId();
        $self->ranking = $submission->getRanking();
        $self->lastActive = $submission->getLastActive();
        $self->netScore = $submission->getNetScore();
        $self->commentCount = $submission->getCommentCount();

        return $self;
    }
}
