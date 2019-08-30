<?php

namespace App\Entity;

use App\Entity\Contracts\VotableInterface;
use App\Entity\Exception\BannedFromForumException;
use App\Entity\Exception\SubmissionLockedException;
use App\Entity\Traits\VotableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CommentRepository")
 * @ORM\Table(indexes={
 *     @ORM\Index(name="comments_timestamp_idx", columns={"timestamp"}),
 *     @ORM\Index(name="comments_search_idx", columns={"search_doc"})
 * })
 */
class Comment implements VotableInterface {
    use VotableTrait {
        vote as private realVote;
        getNetScore as private getRealNetScore;
    }

    public const MAX_BODY_LENGTH = 10000;

    /**
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Id()
     *
     * @Groups({"comment:read", "abbreviated_relations"})
     *
     * @var int|null
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     *
     * @Groups({"comment:read"})
     *
     * @var string
     */
    private $body;

    /**
     * @ORM\Column(type="datetimetz")
     *
     * @Groups({"comment:read"})
     *
     * @var \DateTime
     */
    private $timestamp;

    /**
     * @ORM\JoinColumn(nullable=false)
     * @ORM\ManyToOne(targetEntity="User", inversedBy="comments")
     *
     * @Groups({"comment:read"})
     *
     * @var User
     */
    private $user;

    /**
     * @ORM\JoinColumn(nullable=false)
     * @ORM\ManyToOne(targetEntity="Submission", inversedBy="comments")
     *
     * @Groups({"comment:read"})
     *
     * @var Submission
     */
    private $submission;

    /**
     * @ORM\ManyToOne(targetEntity="Comment", inversedBy="children")
     *
     * @var Comment|null
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="parent", cascade={"remove"})
     *
     * @var Comment[]|Collection
     */
    private $children;

    /**
     * @ORM\OneToMany(targetEntity="CommentVote", mappedBy="comment",
     *     fetch="EXTRA_LAZY", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @var CommentVote[]|Collection
     */
    private $votes;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     *
     * @Groups({"comment:read"})
     *
     * @var bool
     */
    private $softDeleted = false;

    /**
     * @ORM\Column(type="inet", nullable=true)
     *
     * @var string|null
     */
    private $ip;

    /**
     * @ORM\Column(type="datetimetz", nullable=true)
     *
     * @Groups({"comment:read"})
     *
     * @var \DateTime|null
     */
    private $editedAt;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     *
     * @Groups({"comment:read"})
     *
     * @var bool
     */
    private $moderated = false;

    /**
     * @ORM\Column(type="text")
     *
     * @Groups("comment:read")
     *
     * @var string
     */
    private $userFlag = UserFlags::FLAG_NONE;

    /**
     * @ORM\OneToMany(targetEntity="CommentNotification", mappedBy="comment", cascade={"remove"})
     *
     * @var CommentNotification[]|Collection
     */
    private $notifications;

    /**
     * @ORM\OneToMany(targetEntity="CommentMention", mappedBy="comment", cascade={"remove"})
     *
     * @var CommentMention[]|Collection
     */
    private $mentions;

    /**
     * @ORM\Column(type="integer")
     *
     * @Groups({"comment:read"})
     *
     * @var int
     */
    private $netScore = 0;

    /**
     * @ORM\Column(type="tsvector", nullable=true)
     */
    private $searchDoc;

    /**
     * @Groups({"comment:read"})
     */
    protected $upvotes;

    /**
     * @Groups({"comment:read"})
     */
    protected $downvotes;

    public function __construct(
        string $body,
        User $user,
        Submission $submission,
        ?string $ip,
        \DateTime $timestamp = null
    ) {
        if ($ip !== null && !filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('Invalid IP address');
        }

        if ($submission->isLocked() && !$user->isAdmin()) {
            throw new SubmissionLockedException();
        }

        if ($submission->getForum()->userIsBanned($user)) {
            throw new BannedFromForumException();
        }

        $this->body = $body;
        $this->user = $user;
        $this->submission = $submission;
        $this->ip = $user->isTrustedOrAdmin() ? null : $ip;
        $this->timestamp = $timestamp ?: new \DateTime('@'.time());
        $this->children = new ArrayCollection();
        $this->votes = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->mentions = new ArrayCollection();
        $this->vote(self::VOTE_UP, $user, $ip);
        $this->notify();

        $submission->addComment($this);
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getBody(): string {
        return $this->body;
    }

    public function setBody(string $body): void {
        $this->body = $body;
    }

    public function getTimestamp(): \DateTime {
        return $this->timestamp;
    }

    public function getUser(): User {
        return $this->user;
    }

    public function getSubmission(): Submission {
        return $this->submission;
    }

    public function getParent(): ?self {
        return $this->parent;
    }

    /**
     * @Groups({"comment:read"})
     * @SerializedName("parent")
     */
    public function getParentId(): ?int {
        return $this->parent ? $this->parent->id : null;
    }

    /**
     * Get replies, ordered by descending net score.
     *
     * @return Comment[]
     */
    public function getChildren(): array {
        $children = $this->children->toArray();

        usort($children, function (self $a, self $b) {
            return $b->getNetScore() <=> $a->getNetScore();
        });

        return $children;
    }

    /**
     * @Groups({"comment:read"})
     */
    public function getReplyCount(): int {
        return \count($this->children);
    }

    public function addReply(self $reply): void {
        if ($reply === $this) {
            throw new \DomainException('$reply cannot be self');
        }

        if ($reply->parent) {
            throw new \DomainException('Cannot reassign parent of comment');
        }

        $reply->parent = $this;
        $reply->notify();
    }

    public function removeReply(self $reply): void {
        $this->children->removeElement($reply);
    }

    public function getVotes(): Collection {
        return $this->votes;
    }

    public function addMention(User $mentioned): void {
        if ($mentioned === $this->getUser()) {
            // don't notify yourself
            return;
        }

        if ($mentioned->isAccountDeleted()) {
            return;
        }

        if (!$mentioned->getNotifyOnMentions()) {
            // don't notify users who've disabled mention notifications
            return;
        }

        if ($mentioned->isBlocking($this->getUser())) {
            // don't notify users blocking you
            return;
        }

        $replyingTo = ($this->getParent() ?: $this->getSubmission())->getUser();

        if ($replyingTo === $mentioned && $replyingTo->getNotifyOnReply()) {
            // don't notify users who'll get a notification for the reply anyway
            return;
        }

        $mentioned->sendNotification(new CommentMention($mentioned, $this));
    }

    protected function createVote(int $choice, User $user, ?string $ip): Vote {
        return new CommentVote($choice, $user, $ip, $this);
    }

    public function vote(int $choice, User $user, ?string $ip): void {
        if ($choice !== self::VOTE_NONE && $this->submission->getForum()->userIsBanned($user)) {
            throw new BannedFromForumException();
        }

        $this->realVote($choice, $user, $ip);

        $this->netScore = $this->getRealNetScore();
    }

    public function isSoftDeleted(): bool {
        return $this->softDeleted && $this->body === '';
    }

    /**
     * Delete a comment without deleting its replies.
     */
    public function softDelete(): void {
        $this->softDeleted = true;
        $this->body = '';
        $this->userFlag = UserFlags::FLAG_NONE;
        $this->submission->updateCommentCount();
        $this->submission->updateRanking();
        $this->submission->updateLastActive();
    }

    public function getIp(): ?string {
        return $this->ip;
    }

    public function getEditedAt(): ?\DateTime {
        return $this->editedAt;
    }

    public function setEditedAt(?\DateTime $editedAt): void {
        $this->editedAt = $editedAt;
    }

    public function isModerated(): bool {
        return $this->moderated;
    }

    public function setModerated(bool $moderated): void {
        $this->moderated = $moderated;
    }

    public function getUserFlag(): string {
        return $this->userFlag;
    }

    public function setUserFlag(string $userFlag): void {
        UserFlags::checkUserFlag($userFlag);

        $this->userFlag = $userFlag;
    }

    private function notify(): void {
        $receiver = ($this->parent ?: $this->submission)->getUser();

        if (
            $this->user === $receiver ||
            $receiver->isAccountDeleted() ||
            !$receiver->getNotifyOnReply() ||
            $receiver->isBlocking($this->user)
        ) {
            // don't send notifications to oneself, to a user who's disabled
            // them, or to a user who's blocked the user replying
            return;
        }

        $receiver->sendNotification(new CommentNotification($receiver, $this));
    }

    public function getNetScore(): int {
        return $this->netScore;
    }
}
