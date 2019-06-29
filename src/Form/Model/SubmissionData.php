<?php

namespace App\Form\Model;

use App\Entity\Forum;
use App\Entity\Submission;
use App\Entity\User;
use App\Entity\UserFlags;
use App\Validator\Constraints\NotForumBanned;
use App\Validator\Constraints\RateLimit;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @RateLimit(period="5 minutes", max=15, groups={"create"}, entityClass=Submission::class)
 * @RateLimit(period="1 hour", max=3, groups={"untrusted_user_create"}, entityClass=Submission::class)
 */
class SubmissionData {
    private $entityId;

    /**
     * @Assert\NotBlank(groups={"create", "edit"})
     * @Assert\Length(max=Submission::MAX_TITLE_LENGTH, groups={"create", "edit"})
     *
     * @var string|null
     */
    private $title;

    /**
     * @Assert\Length(max=Submission::MAX_URL_LENGTH, charset="8bit", groups={"create", "edit"})
     * @Assert\Url(protocols={"http", "https"}, groups={"create", "edit"})
     *
     * @see https://stackoverflow.com/questions/417142/
     *
     * @var string|null
     */
    private $url;

    /**
     * @Assert\Length(max=Submission::MAX_BODY_LENGTH, groups={"create", "edit"})
     *
     * @var string|null
     */
    private $body;

    private $userFlag = UserFlags::FLAG_NONE;

    /**
     * @NotForumBanned()
     * @Assert\NotBlank(groups={"create", "edit"})
     *
     * @var Forum|null
     */
    private $forum;

    public function __construct(Forum $forum = null) {
        $this->forum = $forum;
    }

    public static function createFromSubmission(Submission $submission): self {
        $self = new self();
        $self->entityId = $submission->getId();
        $self->title = $submission->getTitle();
        $self->url = $submission->getUrl();
        $self->body = $submission->getBody();
        $self->userFlag = $submission->getUserFlag();
        $self->forum = $submission->getForum();

        return $self;
    }

    public function toSubmission(User $user, ?string $ip): Submission {
        $submission = new Submission($this->title, $this->url, $this->body, $this->forum, $user, $ip);
        $submission->setUserFlag($this->userFlag);

        return $submission;
    }

    public function updateSubmission(Submission $submission, User $editingUser) {
        if (
            $this->url !== $submission->getUrl() ||
            $this->title !== $submission->getTitle() ||
            $this->body !== $submission->getBody()
        ) {
            $submission->setTitle($this->title);
            $submission->setUrl($this->url);
            $submission->setBody($this->body);
            $submission->setEditedAt(new \DateTime('@'.time()));

            if (!$submission->isModerated()) {
                $submission->setModerated($submission->getUser() !== $editingUser);
            }
        }

        $submission->setUserFlag($this->userFlag);
    }

    public function getEntityId() {
        return $this->entityId;
    }

    public function getTitle() {
        return $this->title;
    }

    public function setTitle($title) {
        $this->title = $title;
    }

    public function getUrl() {
        return $this->url;
    }

    public function setUrl($url) {
        $this->url = $url;
    }

    public function getBody() {
        return $this->body;
    }

    public function setBody($body) {
        $this->body = $body;
    }

    public function getUserFlag() {
        return $this->userFlag;
    }

    public function setUserFlag($userFlag) {
        $this->userFlag = $userFlag;
    }

    public function getForum() {
        return $this->forum;
    }

    public function setForum($forum) {
        $this->forum = $forum;
    }
}
