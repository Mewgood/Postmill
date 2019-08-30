<?php

namespace App\Form\Model;

use App\Entity\Comment;
use App\Entity\Submission;
use App\Entity\User;
use App\Entity\UserFlags;
use App\Validator\Constraints\NotForumBanned;
use App\Validator\Constraints\RateLimit;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @RateLimit(period="5 minutes", max=10, groups={"create"}, entityClass=Comment::class, errorPath="body")
 * @NotForumBanned(forumPath="submission.forum", errorPath="body")
 */
class CommentData {
    /**
     * @var int|null
     */
    private $entityId;

    /**
     * @var Submission
     */
    private $submission;

    /**
     * @Assert\NotBlank(message="The comment must not be empty.")
     * @Assert\Regex("/[[:graph:]]/u", message="The comment must not be empty.")
     * @Assert\Length(max=Comment::MAX_BODY_LENGTH)
     *
     * @var string|null
     */
    private $body;

    /**
     * @var string|null
     */
    private $userFlag = UserFlags::FLAG_NONE;

    public static function createFromComment(Comment $comment): self {
        $self = new self($comment->getSubmission());
        $self->entityId = $comment->getId();
        $self->submission = $comment->getSubmission();
        $self->body = $comment->getBody();
        $self->userFlag = $comment->getUserFlag();

        return $self;
    }

    public function __construct(Submission $submission) {
        $this->submission = $submission;
    }

    public function toComment(User $user, ?string $ip): Comment {
        $comment = new Comment($this->body, $user, $this->submission, $ip);
        $comment->setUserFlag($this->userFlag);

        return $comment;
    }

    public function updateComment(Comment $comment, User $editingUser): void {
        $comment->setUserFlag($this->userFlag);

        if ($this->body !== $comment->getBody()) {
            $comment->setBody($this->body);
            $comment->setEditedAt(new \DateTime('@'.time()));

            if (!$comment->isModerated()) {
                $comment->setModerated($comment->getUser() !== $editingUser);
            }
        }
    }

    public function getEntityId(): ?int {
        return $this->entityId;
    }

    public function getBody(): ?string {
        return $this->body;
    }

    public function setBody(?string $body): void {
        $this->body = $body;
    }

    public function getUserFlag(): ?string {
        return $this->userFlag;
    }

    public function setUserFlag(?string $userFlag): void {
        $this->userFlag = $userFlag;
    }

    public function getSubmission(): Submission {
        return $this->submission;
    }
}
