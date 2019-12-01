<?php

namespace App\Entity;

use App\Entity\Contracts\BackgroundImageInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\Mapping as ORM;
use Pagerfanta\Adapter\DoctrineCollectionAdapter;
use Pagerfanta\Adapter\DoctrineSelectableAdapter;
use Pagerfanta\Pagerfanta;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ForumRepository")
 * @ORM\Table(indexes={
 *     @ORM\Index(name="forum_featured_idx", columns={"featured"})
 * }, uniqueConstraints={
 *     @ORM\UniqueConstraint(name="forums_name_idx", columns={"name"}),
 *     @ORM\UniqueConstraint(name="forums_normalized_name_idx", columns={"normalized_name"}),
 * })
 */
class Forum implements BackgroundImageInterface {
    /**
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Id()
     *
     * @var int|null
     */
    private $id;

    /**
     * @ORM\Column(type="text", unique=true)
     *
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(type="text", unique=true)
     *
     * @var string
     */
    private $normalizedName;

    /**
     * @ORM\Column(type="text")
     *
     * @var string
     */
    private $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @var string|null
     */
    private $description;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @var string
     */
    private $sidebar;

    /**
     * @ORM\OneToMany(targetEntity="Moderator", mappedBy="forum", cascade={"persist", "remove"})
     *
     * @var Moderator[]|Collection
     */
    private $moderators;

    /**
     * @ORM\OneToMany(targetEntity="Submission", mappedBy="forum", cascade={"remove"}, fetch="EXTRA_LAZY")
     *
     * @var Submission[]|Collection
     */
    private $submissions;

    /**
     * @ORM\Column(type="datetimetz")
     *
     * @var \DateTime
     */
    private $created;

    /**
     * @ORM\OneToMany(targetEntity="ForumSubscription", mappedBy="forum",
     *     cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     *
     * @var ForumSubscription[]|Collection|Selectable
     */
    private $subscriptions;

    /**
     * @ORM\OneToMany(targetEntity="ForumBan", mappedBy="forum", cascade={"persist", "remove"})
     *
     * @var ForumBan[]|Collection|Selectable
     */
    private $bans;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     *
     * @var bool
     */
    private $featured = false;

    /**
     * @ORM\ManyToOne(targetEntity="ForumCategory", inversedBy="forums")
     *
     * @var ForumCategory|null
     */
    private $category;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @var string|null
     */
    private $lightBackgroundImage;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @var string|null
     */
    private $darkBackgroundImage;

    /**
     * @ORM\Column(type="text", options={"default": BackgroundImageInterface::BACKGROUND_TILE})
     *
     * @var string
     */
    private $backgroundImageMode = BackgroundImageInterface::BACKGROUND_TILE;

    /**
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @ORM\ManyToOne(targetEntity="Theme")
     *
     * @var Theme|null
     */
    private $suggestedTheme;

    /**
     * @ORM\OneToMany(targetEntity="ForumLogEntry", mappedBy="forum", cascade={"persist", "remove"})
     * @ORM\OrderBy({"timestamp": "DESC"})
     *
     * @var ForumLogEntry[]|Collection
     */
    private $logEntries;

    public function __construct(
        string $name,
        string $title,
        string $description,
        string $sidebar,
        User $user = null,
        \DateTime $created = null
    ) {
        $this->setName($name);
        $this->title = $title;
        $this->description = $description;
        $this->sidebar = $sidebar;
        $this->created = $created ?: new \DateTime('@'.time());
        $this->bans = new ArrayCollection();
        $this->moderators = new ArrayCollection();
        $this->submissions = new ArrayCollection();
        $this->subscriptions = new ArrayCollection();
        $this->logEntries = new ArrayCollection();

        if ($user) {
            $this->addModerator(new Moderator($this, $user, $this->created));
            $this->subscribe($user);
        }
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getName(): string {
        return $this->name;
    }

    public function setName(string $name): void {
        $this->name = $name;
        $this->normalizedName = self::normalizeName($name);
    }

    public function getNormalizedName(): ?string {
        return $this->normalizedName;
    }

    public function getTitle(): string {
        return $this->title;
    }

    public function setTitle(string $title): void {
        $this->title = $title;
    }

    public function getDescription(): ?string {
        return $this->description;
    }

    public function setDescription(?string $description): void {
        $this->description = $description;
    }

    public function getSidebar(): string {
        return $this->sidebar;
    }

    public function setSidebar(string $sidebar): void {
        $this->sidebar = $sidebar;
    }

    /**
     * @return Collection|Moderator[]
     */
    public function getModerators(): Collection {
        return $this->moderators;
    }

    /**
     * @return Pagerfanta|Moderator[]
     */
    public function getPaginatedModerators(int $page, int $maxPerPage = 25): Pagerfanta {
        $criteria = Criteria::create()->orderBy(['timestamp' => 'ASC']);

        $moderators = new Pagerfanta(new DoctrineSelectableAdapter($this->moderators, $criteria));
        $moderators->setMaxPerPage($maxPerPage);
        $moderators->setCurrentPage($page);

        return $moderators;
    }

    public function userIsModerator($user, bool $adminsAreMods = true): bool {
        if (!$user instanceof User) {
            return false;
        }

        if ($adminsAreMods && $user->isAdmin()) {
            return true;
        }

        // optimised to significantly lessen the number of SQL queries performed
        // when logged in as the user being checked.
        $user->getModeratorTokens()->get(-1);

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('forum', $this));

        return \count($user->getModeratorTokens()->matching($criteria)) > 0;
    }

    public function addModerator(Moderator $moderator): void {
        if (!$this->moderators->contains($moderator)) {
            $this->moderators->add($moderator);
        }
    }

    public function userCanDelete($user): bool {
        if (!$user instanceof User) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (!$this->userIsModerator($user)) {
            return false;
        }

        return \count($this->submissions) === 0;
    }

    /**
     * @return Collection|Submission[]
     */
    public function getSubmissions(): Collection {
        return $this->submissions;
    }

    public function getCreated(): \DateTime {
        return $this->created;
    }

    /**
     * @return ForumSubscription[]|Collection|Selectable
     */
    public function getSubscriptions(): Collection {
        return $this->subscriptions;
    }

    public function isSubscribed(User $user): bool {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('user', $user));

        return \count($this->subscriptions->matching($criteria)) > 0;
    }

    public function subscribe(User $user): void {
        if (!$this->isSubscribed($user)) {
            $this->subscriptions->add(new ForumSubscription($user, $this));
        }
    }

    public function unsubscribe(User $user): void {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('user', $user));

        $subscription = $this->subscriptions->matching($criteria)->first();

        if ($subscription) {
            $this->subscriptions->removeElement($subscription);
        }
    }

    public function userIsBanned(User $user): bool {
        if ($user->isAdmin()) {
            // should we check for mod permissions too?
            return false;
        }

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('user', $user))
            ->orderBy(['timestamp' => 'DESC'])
            ->setMaxResults(1);

        /** @var ForumBan|null $ban */
        $ban = $this->bans->matching($criteria)->first() ?: null;

        if (!$ban || !$ban->isBan()) {
            return false;
        }

        return !$ban->isExpired();
    }

    /**
     * @return Pagerfanta|ForumBan[]
     */
    public function getPaginatedBansByUser(User $user, int $page, int $maxPerPage = 25): Pagerfanta {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('user', $user))
            ->orderBy(['timestamp' => 'DESC']);

        $pager = new Pagerfanta(new DoctrineSelectableAdapter($this->bans, $criteria));
        $pager->setMaxPerPage($maxPerPage);
        $pager->setCurrentPage($page);

        return $pager;
    }

    public function addBan(ForumBan $ban): void {
        if (!$this->bans->contains($ban)) {
            $this->bans->add($ban);

            $this->logEntries->add(new ForumLogBan($ban));
        }
    }

    public function isFeatured(): bool {
        return $this->featured;
    }

    public function setFeatured(bool $featured): void {
        $this->featured = $featured;
    }

    public function getCategory(): ?ForumCategory {
        return $this->category;
    }

    public function setCategory(?ForumCategory $category): void {
        $this->category = $category;
    }

    public function getLightBackgroundImage(): ?string {
        return $this->lightBackgroundImage;
    }

    public function setLightBackgroundImage(?string $lightBackgroundImage): void {
        $this->lightBackgroundImage = $lightBackgroundImage;
    }

    public function getDarkBackgroundImage(): ?string {
        return $this->darkBackgroundImage;
    }

    public function setDarkBackgroundImage(?string $darkBackgroundImage): void {
        $this->darkBackgroundImage = $darkBackgroundImage;
    }

    public function getBackgroundImageMode(): string {
        return $this->backgroundImageMode;
    }

    public function setBackgroundImageMode(string $backgroundImageMode): void {
        $this->backgroundImageMode = $backgroundImageMode;
    }

    public function getSuggestedTheme(): ?Theme {
        return $this->suggestedTheme;
    }

    public function setSuggestedTheme(?Theme $suggestedTheme): void {
        $this->suggestedTheme = $suggestedTheme;
    }

    /**
     * @return Pagerfanta|ForumLogEntry[]
     */
    public function getPaginatedLogEntries(int $page, int $max = 50): Pagerfanta {
        $pager = new Pagerfanta(new DoctrineCollectionAdapter($this->logEntries));
        $pager->setMaxPerPage($max);
        $pager->setCurrentPage($page);

        return $pager;
    }

    public function addLogEntry(ForumLogEntry $entry): void {
        if (!$this->logEntries->contains($entry)) {
            $this->logEntries->add($entry);
        }
    }

    public static function normalizeName(string $name): string {
        return mb_strtolower($name, 'UTF-8');
    }
}
