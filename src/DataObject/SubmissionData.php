<?php

namespace App\DataObject;

use App\Entity\Forum;
use App\Entity\Image;
use App\Entity\Submission;
use App\Entity\User;
use App\Entity\UserFlags;
use App\Serializer\Contracts\NormalizeMarkdownInterface;
use App\Utils\Slugger;
use App\Validator\Constraints\NotForumBanned;
use App\Validator\Constraints\RateLimit;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @RateLimit(period="5 minutes", max=15, groups={"create"}, entityClass=Submission::class)
 * @RateLimit(period="1 hour", max=3, groups={"unwhitelisted_user_create"}, entityClass=Submission::class)
 */
class SubmissionData implements NormalizeMarkdownInterface {
    /**
     * @Groups({"submission:read", "abbreviated_relations"})
     *
     * @var int|null
     */
    private $id;

    /**
     * @Assert\NotBlank(groups={"create", "update"})
     * @Assert\Length(max=Submission::MAX_TITLE_LENGTH, groups={"create", "update"})
     *
     * @Groups({"submission:read", "submission:create", "submission:update"})
     *
     * @var string|null
     */
    private $title;

    /**
     * @Assert\Length(max=Submission::MAX_URL_LENGTH, charset="8bit", groups={"url"})
     * @Assert\Url(protocols={"http", "https"}, groups={"url"})
     *
     * @Groups({"submission:read", "submission:create", "submission:update"})
     *
     * @see https://stackoverflow.com/questions/417142/
     *
     * @var string|null
     */
    private $url;

    /**
     * @Assert\Length(max=Submission::MAX_BODY_LENGTH, groups={"create", "update"})
     *
     * @Groups({"submission:read", "submission:create", "submission:update"})
     *
     * @var string|null
     */
    private $body;

    /**
     * @Assert\Choice(Submission::MEDIA_TYPES, strict=true, groups={"media"})
     * @Assert\NotBlank(groups={"media"})
     *
     * @Groups("submission:read")
     *
     * @var string|null
     */
    private $mediaType = Submission::MEDIA_URL;

    /**
     * @Groups("submission:read")
     *
     * @var int
     */
    private $commentCount = 0;

    /**
     * @Groups("submission:read")
     *
     * @var \DateTimeImmutable|null
     */
    private $timestamp;

    /**
     * @Groups("submission:read")
     *
     * @var \DateTimeImmutable|null
     */
    private $lastActive;

    /**
     * @Groups("submission:read")
     *
     * @var string
     */
    private $visibility;

    /**
     * @NotForumBanned(groups={"create", "update"})
     * @Assert\NotBlank(groups={"create"})
     *
     * @Groups({"submission:read", "submission:create", "abbreviated_relations"})
     *
     * @var Forum|null
     */
    private $forum;

    /**
     * @Groups({"submission:read", "abbreviated_relations"})
     *
     * @var User
     */
    private $user;

    /**
     * @Groups("submission:read")
     *
     * @var int
     */
    private $netScore;

    /**
     * @Groups("submission:read")
     *
     * @var int
     */
    private $upvotes;

    /**
     * @Groups("submission:read")
     *
     * @var int
     */
    private $downvotes;

    /**
     * @Groups("submission:read")
     *
     * @var Image|null
     */
    private $image;

    /**
     * @Groups("submission:read")
     *
     * @var bool
     */
    private $sticky = false;

    /**
     * @Groups("submission:read")
     *
     * @var \DateTimeImmutable|null
     */
    private $editedAt;

    /**
     * @Groups("submission:read")
     *
     * @var bool
     */
    private $moderated;

    /**
     * @Groups("submission:read")
     *
     * @var string
     */
    private $userFlag = UserFlags::FLAG_NONE;

    /**
     * @Groups("submission:read")
     *
     * @var bool
     */
    private $locked = false;

    public function __construct(Submission $submission = null) {
        if ($submission) {
            $this->id = $submission->getId();
            $this->title = $submission->getTitle();
            $this->url = $submission->getUrl();
            $this->body = $submission->getBody();
            $this->mediaType = $submission->getMediaType();
            $this->commentCount = $submission->getCommentCount();
            $this->timestamp = $submission->getTimestamp();
            $this->lastActive = $submission->getLastActive();
            $this->visibility = $submission->getVisibility();
            $this->forum = $submission->getForum();
            $this->user = $submission->getUser();
            $this->netScore = $submission->getNetScore();
            $this->upvotes = $submission->getUpvotes();
            $this->downvotes = $submission->getDownvotes();
            $this->image = $submission->getImage();
            $this->sticky = $submission->isSticky();
            $this->editedAt = $submission->getEditedAt();
            $this->moderated = $submission->isModerated();
            $this->userFlag = $submission->getUserFlag();
            $this->locked = $submission->isLocked();
        }
    }

    public function toSubmission(User $user, ?string $ip): Submission {
        $submission = new Submission($this->title, $this->url, $this->body, $this->forum, $user, $ip);
        $submission->setUserFlag($this->userFlag);
        $submission->setSticky($this->sticky);
        $submission->setLocked($this->locked);

        if ($this->mediaType === Submission::MEDIA_IMAGE) {
            $submission->setUrl(null);

            if ($this->image) {
                $submission->setImage($this->image);
                $submission->setMediaType($this->mediaType);
            }
        }

        return $submission;
    }

    public function updateSubmission(Submission $submission, User $editingUser): void {
        if (
            $this->url !== $submission->getUrl() ||
            $this->title !== $submission->getTitle() ||
            $this->body !== $submission->getBody()
        ) {
            $this->editedAt = new \DateTimeImmutable('@'.time());
            $this->moderated = $this->moderated || $editingUser !== $submission->getUser();
        }

        $submission->setTitle($this->title);
        $submission->setUrl($this->url);
        $submission->setBody($this->body);
        $submission->setEditedAt($this->editedAt);
        $submission->setUserFlag($this->userFlag);
        $submission->setModerated($this->moderated);
        $submission->setSticky($this->sticky);
        $submission->setLocked($this->locked);
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getTitle(): ?string {
        return $this->title;
    }

    public function setTitle(?string $title): void {
        $this->title = $title;
    }

    /**
     * @Groups({"submission:read", "abbreviated_relations"})
     */
    public function getSlug(): ?string {
        return isset($this->title) ? Slugger::slugify($this->title) : null;
    }

    public function getUrl(): ?string {
        return $this->url;
    }

    public function setUrl(?string $url): void {
        $this->url = $url;
    }

    public function getBody(): ?string {
        return $this->body;
    }

    public function setBody(?string $body): void {
        $this->body = $body;
    }

    public function getMediaType(): ?string {
        return $this->mediaType;
    }

    public function setMediaType(string $mediaType): void {
        $this->mediaType = $mediaType;
    }

    public function getCommentCount(): int {
        return $this->commentCount;
    }

    public function getTimestamp(): ?\DateTimeImmutable {
        return $this->timestamp;
    }

    public function getLastActive(): ?\DateTimeImmutable {
        return $this->lastActive;
    }

    public function getVisibility(): ?string {
        return $this->visibility;
    }

    public function getForum(): ?Forum {
        return $this->forum;
    }

    public function setForum(?Forum $forum): void {
        $this->forum = $forum;
    }

    public function getUser(): ?User {
        return $this->user;
    }

    public function getNetScore(): int {
        return $this->netScore;
    }

    public function getUpvotes(): int {
        return $this->upvotes;
    }

    public function getDownvotes(): int {
        return $this->downvotes;
    }

    public function getImage(): ?Image {
        return $this->image;
    }

    public function setImage(?Image $image): void {
        $this->image = $image;
    }

    public function isSticky(): bool {
        return $this->sticky;
    }

    public function setSticky(bool $sticky): void {
        $this->sticky = $sticky;
    }

    public function getEditedAt(): ?\DateTimeImmutable {
        return $this->editedAt;
    }

    public function isModerated(): bool {
        return $this->moderated;
    }

    public function getUserFlag(): ?string {
        return $this->userFlag;
    }

    public function setUserFlag(?string $userFlag): void {
        $this->userFlag = $userFlag;
    }

    public function isLocked(): bool {
        return $this->locked;
    }

    public function setLocked(bool $locked): void {
        $this->locked = $locked;
    }

    public function getMarkdownFields(): iterable {
        yield 'body';
    }
}
