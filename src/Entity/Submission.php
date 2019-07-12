<?php

namespace App\Entity;

use App\Entity\Exception\BannedFromForumException;
use App\Entity\Exception\SubmissionLockedException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SubmissionRepository")
 * @ORM\Table(indexes={
 *     @ORM\Index(name="submissions_timestamp_idx", columns={"timestamp"}),
 *     @ORM\Index(name="submissions_ranking_id_idx", columns={"ranking", "id"}),
 *     @ORM\Index(name="submissions_last_active_id_idx", columns={"last_active", "id"}),
 *     @ORM\Index(name="submissions_comment_count_id_idx", columns={"comment_count", "id"}),
 *     @ORM\Index(name="submissions_net_score_id_idx", columns={"net_score", "id"}),
 *     @ORM\Index(name="submissions_search_idx", columns={"search_doc"}),
 *     @ORM\Index(name="submissions_visibility_idx", columns={"visibility"}),
 *     @ORM\Index(name="submissions_image_idx", columns={"image"}),
 * })
 */
class Submission extends Votable {
    public const MEDIA_TYPES = [self::MEDIA_URL, self::MEDIA_IMAGE];
    public const MEDIA_URL = 'url';
    public const MEDIA_IMAGE = 'image';

    public const MAX_TITLE_LENGTH = 300;
    public const MAX_URL_LENGTH = 2000;
    public const MAX_BODY_LENGTH = 25000;

    public const VISIBILITY_VISIBLE = 'visible';
    public const VISIBILITY_DELETED = 'deleted';

    public const FRONT_FEATURED = 'featured';
    public const FRONT_SUBSCRIBED = 'subscribed';
    public const FRONT_ALL = 'all';
    public const FRONT_MODERATED = 'moderated';
    public const SORT_ACTIVE = 'active';
    public const SORT_HOT = 'hot';
    public const SORT_NEW = 'new';
    public const SORT_TOP = 'top';
    public const SORT_CONTROVERSIAL = 'controversial';
    public const SORT_MOST_COMMENTED = 'most_commented';
    public const TIME_DAY = 'day';
    public const TIME_WEEK = 'week';
    public const TIME_MONTH = 'month';
    public const TIME_YEAR = 'year';
    public const TIME_ALL = 'all';

    public const FRONT_PAGE_OPTIONS = [
        self::FRONT_FEATURED,
        self::FRONT_SUBSCRIBED,
        self::FRONT_ALL,
        self::FRONT_MODERATED,
    ];

    public const SORT_OPTIONS = [
        self::SORT_ACTIVE,
        self::SORT_HOT,
        self::SORT_NEW,
        self::SORT_TOP,
        self::SORT_CONTROVERSIAL,
        self::SORT_MOST_COMMENTED,
    ];

    public const TIME_OPTIONS = [
        self::TIME_DAY,
        self::TIME_WEEK,
        self::TIME_MONTH,
        self::TIME_YEAR,
        self::TIME_ALL,
    ];

    private const DOWNVOTED_CUTOFF = -5;
    private const NETSCORE_MULTIPLIER = 1800;
    private const COMMENT_MULTIPLIER = 5000;
    private const COMMENT_DOWNVOTED_MULTIPLIER = 500;
    private const MAX_ADVANTAGE = 86400;
    private const MAX_PENALTY = 43200;

    /**
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Id()
     *
     * @Groups({"submission:read", "abbreviated_relations"})
     *
     * @var int|null
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     *
     * @Groups({"submission:read"})
     *
     * @var string
     */
    private $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @Groups({"submission:read"})
     *
     * @var string|null
     */
    private $url;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @Groups({"submission:read"})
     *
     * @var string|null
     */
    private $body;

    /**
     * @ORM\Column(type="text")
     *
     * @Groups({"submission:read"})
     *
     * @var string
     */
    private $mediaType = self::MEDIA_URL;

    /**
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="submission",
     *     fetch="EXTRA_LAZY", cascade={"remove"})
     * @ORM\OrderBy({"timestamp": "ASC"})
     *
     * @var Comment[]|Collection|Selectable
     */
    private $comments;

    /**
     * @ORM\Column(type="integer")
     *
     * @Groups({"submission:read"})
     *
     * @var int
     */
    private $commentCount = 0;

    /**
     * @ORM\Column(type="datetimetz")
     *
     * @Groups({"submission:read"})
     *
     * @var \DateTime
     */
    private $timestamp;

    /**
     * @ORM\Column(type="datetimetz")
     *
     * @Groups({"submission:read"})
     *
     * @var \DateTime
     */
    private $lastActive;

    /**
     * @ORM\Column(type="text")
     *
     * @Groups({"submission:read"})
     *
     * @var string
     */
    private $visibility = self::VISIBILITY_VISIBLE;

    /**
     * @ORM\JoinColumn(nullable=false)
     * @ORM\ManyToOne(targetEntity="Forum", inversedBy="submissions")
     *
     * @Groups({"submission:read", "abbreviated_relations"})
     *
     * @var Forum
     */
    private $forum;

    /**
     * @ORM\JoinColumn(nullable=false)
     * @ORM\ManyToOne(targetEntity="User", inversedBy="submissions")
     *
     * @Groups({"submission:read"})
     *
     * @var User
     */
    private $user;

    /**
     * @ORM\OneToMany(targetEntity="SubmissionVote", mappedBy="submission",
     *     fetch="EXTRA_LAZY", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @var SubmissionVote[]|Collection
     */
    private $votes;

    /**
     * @ORM\OneToMany(targetEntity="SubmissionMention", mappedBy="submission", cascade={"remove"}, orphanRemoval=true)
     *
     * @var SubmissionMention[]|Collection
     */
    private $mentions;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @var string|null
     */
    private $image;

    /**
     * @ORM\Column(type="inet", nullable=true)
     *
     * @var string|null
     */
    private $ip;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Groups({"submission:read"})
     *
     * @var bool
     */
    private $sticky = false;

    /**
     * @ORM\Column(type="bigint")
     *
     * @var int
     */
    private $ranking;

    /**
     * @ORM\Column(type="datetimetz", nullable=true)
     *
     * @Groups({"submission:read"})
     *
     * @var \DateTime|null
     */
    private $editedAt;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     *
     * @Groups({"submission:read"})
     *
     * @var bool
     */
    private $moderated = false;

    /**
     * @ORM\Column(type="smallint", options={"default": 0})
     *
     * @var int
     */
    private $userFlag = UserFlags::FLAG_NONE;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     *
     * @Groups({"submission:read"})
     *
     * @var bool
     */
    private $locked = false;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"submission:read"})
     *
     * @var int
     */
    private $netScore = 0;

    /**
     * @ORM\Column(type="tsvector", nullable=true)
     *
     * @var string
     */
    private $searchDoc;

    /**
     * @Groups({"submission:read"})
     */
    protected $upvotes;

    /**
     * @Groups({"submission:read"})
     */
    protected $downvotes;

    public function __construct(
        string $title,
        ?string $url,
        ?string $body,
        Forum $forum,
        User $user,
        ?string $ip,
        \DateTime $timestamp = null
    ) {
        if ($ip !== null && !filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('Invalid IP address');
        }

        if ($forum->userIsBanned($user)) {
            throw new BannedFromForumException();
        }

        $this->title = $title;
        $this->url = $url;
        $this->body = $body;
        $this->forum = $forum;
        $this->user = $user;
        $this->ip = $user->isTrustedOrAdmin() ? null : $ip;
        $this->timestamp = $timestamp ?? new \DateTime('@'.time());
        $this->comments = new ArrayCollection();
        $this->votes = new ArrayCollection();
        $this->mentions = new ArrayCollection();
        $this->vote($user, $ip, Votable::VOTE_UP);
        $this->updateLastActive();
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getTitle(): string {
        return $this->title;
    }

    public function setTitle(string $title): void {
        $this->title = $title;
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

    public function getMediaType(): string {
        return $this->mediaType;
    }

    public function setMediaType(string $mediaType): void {
        if ($mediaType === self::MEDIA_IMAGE && $this->url !== null) {
            throw new \BadMethodCallException(
                'Submission with URL cannot have image as media type'
            );
        }

        $this->mediaType = $mediaType;
    }

    /**
     * @return Collection|Comment[]
     */
    public function getComments(): Collection {
        return $this->comments;
    }

    /**
     * Get top-level comments, ordered by descending net score.
     *
     * @return Comment[]
     */
    public function getTopLevelComments(): array {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->isNull('parent'));

        $comments = $this->comments->matching($criteria)->toArray();

        if ($comments) {
            usort($comments, [$this, 'descendingNetScoreCmp']);
        }

        return $comments;
    }

    public function addComment(Comment ...$comments): void {
        foreach ($comments as $comment) {
            if (!$this->comments->contains($comment)) {
                $this->comments->add($comment);
            }
        }

        $this->updateCommentCount();
        $this->updateRanking();
        $this->updateLastActive();
    }

    public function removeComment(Comment ...$comments): void {
        // hydrate the collection
        $this->comments->get(-1);

        foreach ($comments as $comment) {
            if ($this->comments->contains($comment)) {
                $this->comments->removeElement($comment);
            }
        }

        $this->updateCommentCount();
        $this->updateRanking();
        $this->updateLastActive();
    }

    public function getCommentCount(): int {
        return $this->commentCount;
    }

    public function updateCommentCount(): void {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('softDeleted', false));

        $this->commentCount = \count($this->comments->matching($criteria));
    }

    public function getTimestamp(): \DateTime {
        return $this->timestamp;
    }

    public function getLastActive(): \DateTime {
        return $this->lastActive;
    }

    public function updateLastActive(): void {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('softDeleted', false))
            ->orderBy(['timestamp' => 'DESC'])
            ->setMaxResults(1);

        $lastComment = $this->comments->matching($criteria)->first();

        if ($lastComment) {
            $this->lastActive = clone $lastComment->getTimestamp();
        } else {
            $this->lastActive = clone $this->getTimestamp();
        }
    }

    public function getVisibility(): string {
        return $this->visibility;
    }

    public function softDelete(): void {
        $this->visibility = self::VISIBILITY_DELETED;
        $this->title = '';
        $this->url = null;
        $this->body = null;
        $this->image = null;
        $this->sticky = false;
        $this->userFlag = 0;
        $this->mentions->clear();
    }

    public function getForum(): Forum {
        return $this->forum;
    }

    public function getUser(): User {
        return $this->user;
    }

    /**
     * @return Collection|SubmissionVote[]
     */
    public function getVotes(): Collection {
        return $this->votes;
    }

    protected function createVote(User $user, ?string $ip, int $choice): Vote {
        return new SubmissionVote($user, $ip, $choice, $this);
    }

    public function vote(User $user, ?string $ip, int $choice): void {
        if ($choice !== self::VOTE_RETRACT) {
            if ($this->visibility === self::VISIBILITY_DELETED) {
                throw new SubmissionLockedException();
            }

            if ($this->forum->userIsBanned($user)) {
                throw new BannedFromForumException();
            }
        }

        parent::vote($user, $ip, $choice);

        $this->updateNetScore();
        $this->updateRanking();
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

        $mentioned->sendNotification(new SubmissionMention($mentioned, $this));
    }

    public function getImage(): ?string {
        return $this->image;
    }

    public function setImage(?string $image): void {
        $this->image = $image;
    }

    public function getIp(): ?string {
        return $this->ip;
    }

    public function isSticky(): bool {
        return $this->sticky;
    }

    public function setSticky(bool $sticky): void {
        $this->sticky = $sticky;
    }

    public function getRanking(): int {
        return $this->ranking;
    }

    public function updateRanking(): void {
        $netScore = $this->getNetScore();
        $netScoreAdvantage = $netScore * self::NETSCORE_MULTIPLIER;

        if ($netScore > self::DOWNVOTED_CUTOFF) {
            $commentAdvantage = $this->getCommentCount() * self::COMMENT_MULTIPLIER;
        } else {
            $commentAdvantage = $this->getCommentCount() * self::COMMENT_DOWNVOTED_MULTIPLIER;
        }

        $advantage = max(min($netScoreAdvantage + $commentAdvantage, self::MAX_ADVANTAGE), -self::MAX_PENALTY);

        $this->ranking = $this->getTimestamp()->getTimestamp() + $advantage;
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

    public function getUserFlag(): int {
        return $this->userFlag;
    }

    /**
     * @Groups({"submission:read"})
     * @SerializedName("userFlag")
     */
    public function getReadableUserFlag(): ?string {
        return UserFlags::toReadable($this->userFlag);
    }

    public function setUserFlag(int $userFlag): void {
        if (!in_array($userFlag, UserFlags::FLAGS, true)) {
            throw new \InvalidArgumentException('Bad flag');
        }

        $this->userFlag = $userFlag;
    }

    public function isLocked(): bool {
        return $this->locked;
    }

    public function setLocked(bool $locked): void {
        $this->locked = $locked;
    }

    public function getNetScore(): int {
        return $this->netScore;
    }

    private function updateNetScore(): void {
        $this->netScore = parent::getNetScore();
    }
}
