<?php

namespace App\Entity;

use App\Entity\Contracts\DomainEventsInterface;
use App\Event\UserCreated;
use App\Event\UserUpdated;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\Mapping as ORM;
use Pagerfanta\Adapter\DoctrineCollectionAdapter;
use Pagerfanta\Adapter\DoctrineSelectableAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\Table(uniqueConstraints={
 *     @ORM\UniqueConstraint(name="users_username_idx", columns={"username"}),
 *     @ORM\UniqueConstraint(name="users_normalized_username_idx", columns={"normalized_username"}),
 * })
 */
class User implements DomainEventsInterface, UserInterface, \Serializable {
    /**
     * User roles, from most privileged to least privileged.
     */
    public const ROLES = [
        'ROLE_ADMIN',
        'ROLE_WHITELISTED',
        'ROLE_USER',
    ];

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
    private $username;

    /**
     * @ORM\Column(type="text", unique=true)
     *
     * @var string
     */
    private $normalizedUsername;

    /**
     * @ORM\Column(type="text")
     *
     * @var string
     */
    private $password;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @var string|null
     */
    private $email;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @var string|null
     */
    private $normalizedEmail;

    /**
     * @ORM\Column(type="datetimetz")
     *
     * @var \DateTime
     */
    private $created;

    /**
     * @ORM\Column(type="datetimetz", nullable=true)
     *
     * @var \DateTime|null
     */
    private $lastSeen;

    /**
     * @ORM\Column(type="inet", nullable=true)
     *
     * @var string|null
     */
    private $registrationIp;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     *
     * @var bool
     */
    private $admin = false;

    /**
     * @ORM\OneToMany(targetEntity="ForumSubscription", mappedBy="user")
     *
     * @var ForumSubscription[]|Collection
     */
    private $subscriptions;

    /**
     * @ORM\OneToMany(targetEntity="Moderator", mappedBy="user")
     *
     * @var Moderator[]|Collection
     */
    private $moderatorTokens;

    /**
     * @ORM\OneToMany(targetEntity="Submission", mappedBy="user", fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"id": "DESC"})
     *
     * @var Submission[]|Collection|Selectable
     */
    private $submissions;

    /**
     * @ORM\OneToMany(targetEntity="SubmissionVote", mappedBy="user", fetch="EXTRA_LAZY")
     *
     * @var SubmissionVote[]|Collection
     */
    private $submissionVotes;

    /**
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="user", fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"id": "DESC"})
     *
     * @var Comment[]|Collection|Selectable
     */
    private $comments;

    /**
     * @ORM\OneToMany(targetEntity="CommentVote", mappedBy="user", fetch="EXTRA_LAZY")
     *
     * @var CommentVote[]|Collection
     */
    private $commentVotes;

    /**
     * @ORM\OneToMany(targetEntity="UserBan", mappedBy="user")
     * @ORM\OrderBy({"timestamp": "ASC"})
     *
     * @var UserBan[]|Collection|Selectable
     */
    private $bans;

    /**
     * @ORM\OneToMany(targetEntity="IpBan", mappedBy="user")
     *
     * @var IpBan[]|Collection|Selectable
     */
    private $ipBans;

    /**
     * @ORM\JoinTable(name="hidden_forums",
     *     joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="forum_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     * @ORM\ManyToMany(targetEntity="Forum")
     *
     * @var Forum[]|Collection|Selectable
     */
    private $hiddenForums;

    /**
     * @ORM\Column(type="text")
     *
     * @var string
     */
    private $locale = 'en';

    /**
     * @ORM\Column(type="text")
     *
     * @var string
     */
    private $timezone;

    /**
     * @ORM\OneToMany(targetEntity="Notification", mappedBy="user", fetch="EXTRA_LAZY", cascade={"persist"})
     *
     * @var Notification[]|Collection|Selectable
     */
    private $notifications;

    /**
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    private $nightMode = false;

    /**
     * @ORM\Column(type="boolean", options={"default": true})
     *
     * @var bool
     */
    private $showCustomStylesheets = true;

    /**
     * @ORM\Column(type="boolean", options={"default": false}, name="trusted")
     *
     * @var bool
     */
    private $whitelisted = false;

    /**
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @ORM\ManyToOne(targetEntity="Theme")
     *
     * @var Theme|null
     */
    private $preferredTheme;

    /**
     * @ORM\OneToMany(targetEntity="UserBlock", mappedBy="blocker", cascade={"persist"}, orphanRemoval=true)
     * @ORM\OrderBy({"timestamp": "DESC"})
     *
     * @var UserBlock[]|Collection|Selectable
     */
    private $blocks;

    /**
     * @ORM\Column(type="text")
     *
     * @var string
     */
    private $frontPage = Submission::FRONT_SUBSCRIBED;

    /**
     * @ORM\Column(type="text")
     *
     * @var string
     */
    private $frontPageSortMode = Submission::SORT_HOT;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     *
     * @var bool
     */
    private $openExternalLinksInNewTab = false;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @var string|null
     */
    private $biography;

    /**
     * @ORM\Column(type="boolean", options={"default": true})
     *
     * @var bool
     */
    private $autoFetchSubmissionTitles = true;

    /**
     * @ORM\Column(type="boolean", options={"default": true})
     *
     * @var bool
     */
    private $enablePostPreviews = true;

    /**
     * @ORM\Column(type="boolean", options={"default": true})
     *
     * @var bool
     */
    private $showThumbnails = true;

    /**
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    private $allowPrivateMessages = true;

    /**
     * @ORM\Column(type="boolean", options={"default": true})
     *
     * @var bool
     */
    private $notifyOnReply = true;

    /**
     * @ORM\Column(type="boolean", options={"default": true})
     *
     * @var bool
     */
    private $notifyOnMentions = true;

    /**
     * @ORM\Column(type="boolean", options={"default": true})
     *
     * @var bool
     */
    private $poppersEnabled = true;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @var string|null
     */
    private $preferredFonts;

    public function __construct(string $username, string $password) {
        $this->setUsername($username);
        $this->password = $password;
        $this->created = new \DateTime('@'.time());
        $this->notifications = new ArrayCollection();
        $this->submissions = new ArrayCollection();
        $this->submissionVotes = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->commentVotes = new ArrayCollection();
        $this->bans = new ArrayCollection();
        $this->ipBans = new ArrayCollection();
        $this->blocks = new ArrayCollection();
        $this->subscriptions = new ArrayCollection();
        $this->moderatorTokens = new ArrayCollection();
        $this->timezone = date_default_timezone_get();
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getUsername(): string {
        return $this->username;
    }

    public function setUsername(string $username): void {
        $this->username = $username;
        $this->normalizedUsername = self::normalizeUsername($username);
    }

    public function getNormalizedUsername(): string {
        return $this->normalizedUsername;
    }

    public function isAccountDeleted(): bool {
        return isset($this->id) && $this->username === "!deleted{$this->id}";
    }

    public function getPassword(): string {
        return $this->password;
    }

    public function setPassword(string $password): void {
        $this->password = $password;
    }

    public function getEmail(): ?string {
        return $this->email;
    }

    public function setEmail(?string $email): void {
        $this->email = $email;
        $this->normalizedEmail = $email ? self::normalizeEmail($email) : null;
    }

    /**
     * Retrieve the normalized email address.
     *
     * Sending email to the normalized address is evil. Use this for lookup,
     * but *always* send to the regular, canon address.
     */
    public function getNormalizedEmail(): ?string {
        return $this->normalizedEmail;
    }

    public function getCreated(): \DateTime {
        return $this->created;
    }

    public function getLastSeen(): ?\DateTime {
        return $this->lastSeen;
    }

    public function setLastSeen(?\DateTime $lastSeen): void {
        $this->lastSeen = $lastSeen;
    }

    public function getRegistrationIp(): ?string {
        return $this->registrationIp;
    }

    public function setRegistrationIp(?string $ip): void {
        if ($ip !== null && !filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('$ip must be NULL or valid IP address');
        }

        $this->registrationIp = $ip;
    }

    public function isAdmin(): bool {
        return $this->admin;
    }

    public function setAdmin(bool $admin): void {
        $this->admin = $admin;
    }

    /**
     * @return Collection|ForumSubscription[]
     */
    public function getSubscriptions(): Collection {
        return $this->subscriptions;
    }

    /**
     * @return Collection|Moderator[]|Selectable
     */
    public function getModeratorTokens(): Collection {
        return $this->moderatorTokens;
    }

    public function getRoles(): array {
        $roles = ['ROLE_USER'];

        if ($this->admin) {
            $roles[] = 'ROLE_ADMIN';
        }

        if ($this->whitelisted) {
            $roles[] = 'ROLE_WHITELISTED';
        }

        return $roles;
    }

    public function getSalt(): ?string {
        // Salt is not needed when bcrypt is used, as the password hash contains
        // the salt.
        return null;
    }

    public function eraseCredentials(): void {
    }

    /**
     * @return Collection|Selectable|Submission[]
     */
    public function getSubmissions(): Collection {
        return $this->submissions;
    }

    public function getSubmissionVotes(): Collection {
        return $this->submissionVotes;
    }

    /**
     * @return Collection|Selectable|Comment[]
     */
    public function getComments(): Collection {
        return $this->comments;
    }

    public function getCommentVotes(): Collection {
        return $this->commentVotes;
    }

    /**
     * @return UserBan[]|Collection
     */
    public function getBans(): Collection {
        return $this->bans;
    }

    public function getActiveBan(): ?UserBan {
        $ban = $this->bans->last();

        if (!$ban instanceof UserBan || !$ban->isBan() || $ban->isExpired()) {
            return null;
        }

        return $ban;
    }

    public function isBanned(): bool {
        return (bool) $this->getActiveBan();
    }

    public function addBan(UserBan $ban): void {
        if (!$this->bans->contains($ban)) {
            $this->bans[] = $ban;
        }
    }

    /**
     * @return Collection|IpBan[]
     */
    public function getIpBans(): Collection {
        return $this->ipBans;
    }

    /**
     * @return Pagerfanta|Forum[]
     */
    public function getPaginatedHiddenForums(int $page): Pagerfanta {
        $pager = new Pagerfanta(new DoctrineCollectionAdapter($this->hiddenForums));
        $pager->setMaxPerPage(25);
        $pager->setCurrentPage($page);

        return $pager;
    }

    public function isHidingForum(Forum $forum): bool {
        return $this->hiddenForums->contains($forum);
    }

    public function hideForum(Forum $forum): void {
        if (!$this->hiddenForums->contains($forum)) {
            $this->hiddenForums->add($forum);
        }
    }

    public function unhideForum(Forum $forum): void {
        $this->hiddenForums->removeElement($forum);
    }

    public function getLocale(): string {
        return $this->locale;
    }

    public function setLocale(string $locale): void {
        $this->locale = $locale;
    }

    public function getTimezone(): \DateTimeZone {
        return new \DateTimeZone($this->timezone);
    }

    public function setTimezone(\DateTimeZone $timezone): void {
        $this->timezone = $timezone->getName();
    }

    /**
     * @return Collection|Selectable|Notification[]
     */
    public function getNotifications(): Collection {
        return $this->notifications;
    }

    public function sendNotification(Notification $notification): void {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
        }
    }

    /**
     * @return Pagerfanta|Notification[]
     */
    public function getPaginatedNotifications(int $page, int $maxPerPage = 25): Pagerfanta {
        $criteria = Criteria::create()->orderBy(['id' => 'DESC']);

        $notifications = new Pagerfanta(new DoctrineSelectableAdapter($this->notifications, $criteria));
        $notifications->setMaxPerPage($maxPerPage);
        $notifications->setCurrentPage($page);

        return $notifications;
    }

    public function isNightMode(): bool {
        return $this->nightMode;
    }

    public function setNightMode(bool $nightMode): void {
        $this->nightMode = $nightMode;
    }

    public function isShowCustomStylesheets(): bool {
        return $this->showCustomStylesheets;
    }

    public function setShowCustomStylesheets(bool $showCustomStylesheets): void {
        $this->showCustomStylesheets = $showCustomStylesheets;
    }

    public function isWhitelisted(): bool {
        return $this->whitelisted;
    }

    public function isWhitelistedOrAdmin(): bool {
        return $this->admin || $this->whitelisted;
    }

    public function setWhitelisted(bool $whitelisted): void {
        $this->whitelisted = $whitelisted;
    }

    public function getPreferredTheme(): ?Theme {
        return $this->preferredTheme;
    }

    public function setPreferredTheme(?Theme $preferredTheme): void {
        $this->preferredTheme = $preferredTheme;
    }

    /**
     * @return Pagerfanta|UserBlock[]
     */
    public function getPaginatedBlocks(int $page, int $maxPerPage = 25) {
        $pager = new Pagerfanta(new DoctrineCollectionAdapter($this->blocks));
        $pager->setMaxPerPage($maxPerPage);
        $pager->setCurrentPage($page);

        return $pager;
    }

    public function isBlocking(self $user): bool {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('blocked', $user));

        return \count($this->blocks->matching($criteria)) > 0;
    }

    public function block(self $user, string $comment = null): void {
        if ($user === $this) {
            throw new \DomainException('Cannot block self');
        }

        if (!$this->isBlocking($user)) {
            $this->blocks->add(new UserBlock($this, $user, $comment));
        }
    }

    public function unblock(self $user): void {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('blocked', $user));

        $block = $this->blocks->matching($criteria)->first() ?: null;

        if ($block) {
            $this->blocks->removeElement($block);
        }
    }

    public function getFrontPage(): string {
        return $this->frontPage;
    }

    public function setFrontPage(string $frontPage): void {
        if (!\in_array($frontPage, Submission::FRONT_PAGE_OPTIONS, true)) {
            throw new \InvalidArgumentException("Unknown choice '$frontPage'");
        }

        $this->frontPage = $frontPage;
    }

    public function getFrontPageSortMode(): string {
        return $this->frontPageSortMode;
    }

    public function setFrontPageSortMode(string $sortMode): void {
        if (!\in_array($sortMode, Submission::SORT_OPTIONS, true)) {
            throw new \InvalidArgumentException("Unknown choice '$sortMode'");
        }

        $this->frontPageSortMode = $sortMode;
    }

    public function openExternalLinksInNewTab(): bool {
        return $this->openExternalLinksInNewTab;
    }

    public function setOpenExternalLinksInNewTab(bool $openExternalLinksInNewTab): void {
        $this->openExternalLinksInNewTab = $openExternalLinksInNewTab;
    }

    public function getBiography(): ?string {
        return $this->biography;
    }

    public function setBiography(?string $biography): void {
        $this->biography = $biography;
    }

    public function autoFetchSubmissionTitles(): bool {
        return $this->autoFetchSubmissionTitles;
    }

    public function setAutoFetchSubmissionTitles(bool $autoFetchSubmissionTitles): void {
        $this->autoFetchSubmissionTitles = $autoFetchSubmissionTitles;
    }

    public function enablePostPreviews(): bool {
        return $this->enablePostPreviews;
    }

    public function setEnablePostPreviews(bool $enablePostPreviews): void {
        $this->enablePostPreviews = $enablePostPreviews;
    }

    public function showThumbnails(): bool {
        return $this->showThumbnails;
    }

    public function setShowThumbnails(bool $showThumbnails): void {
        $this->showThumbnails = $showThumbnails;
    }

    public function allowPrivateMessages(): bool {
        return $this->allowPrivateMessages;
    }

    public function setAllowPrivateMessages(bool $allowPrivateMessages): void {
        $this->allowPrivateMessages = $allowPrivateMessages;
    }

    public function getNotifyOnReply(): bool {
        return $this->notifyOnReply;
    }

    public function setNotifyOnReply(bool $notifyOnReply): void {
        $this->notifyOnReply = $notifyOnReply;
    }

    public function getNotifyOnMentions(): bool {
        return $this->notifyOnMentions;
    }

    public function setNotifyOnMentions(bool $notifyOnMentions): void {
        $this->notifyOnMentions = $notifyOnMentions;
    }

    public function getPreferredFonts(): ?string {
        return $this->preferredFonts;
    }

    public function setPreferredFonts(?string $preferredFonts): void {
        $this->preferredFonts = $preferredFonts;
    }

    public function isPoppersEnabled(): bool {
        return $this->poppersEnabled;
    }

    public function setPoppersEnabled(bool $poppersEnabled): void {
        $this->poppersEnabled = $poppersEnabled;
    }

    /**
     * Returns the normalized form of the username.
     */
    public static function normalizeUsername(string $username): string {
        return mb_strtolower($username, 'UTF-8');
    }

    /**
     * @throws \InvalidArgumentException if `$email` is not a valid address
     */
    public static function normalizeEmail(string $email): string {
        if (substr_count($email, '@') !== 1) {
            throw new \InvalidArgumentException('Invalid email address');
        }

        [$username, $domain] = explode('@', $email, 2);

        switch (strtolower($domain)) {
        case 'gmail.com':
        case 'googlemail.com':
            $username = strtolower($username);
            $username = str_replace('.', '', $username);
            $username = preg_replace('/\+.*/', '', $username);
            $domain = 'gmail.com';
            break;
        // TODO - other common email providers
        default:
            // TODO - do unicode domains need to be handled too?
            $domain = strtolower($domain);
        }

        return sprintf('%s@%s', $username, $domain);
    }

    public function serialize(): string {
        return serialize([$this->id, $this->username, $this->password]);
    }

    public function unserialize($serialized): void {
        [
            $this->id,
            $this->username,
            $this->password,
        ] = @unserialize($serialized, ['allowed_classes' => false]);
    }

    public function onCreate(): Event {
        return new UserCreated($this);
    }

    public function onUpdate($previous): Event {
        \assert($previous instanceof self);

        return new UserUpdated($previous, $this);
    }

    public function onDelete(): Event {
        return new Event();
    }
}
