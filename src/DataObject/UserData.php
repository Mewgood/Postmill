<?php

namespace App\DataObject;

use App\Entity\Submission;
use App\Entity\Theme;
use App\Entity\User;
use App\Serializer\Contracts\NormalizeMarkdownInterface;
use App\Validator\Constraints\RateLimit;
use App\Validator\Constraints\Unique;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @RateLimit(entityClass="App\Entity\User", max="3", period="1 hour",
 *     timestampField="created", ipField="registrationIp", errorPath="username",
 *     message="user.rate_limit", groups={"registration"})
 * @Unique("normalizedUsername", idFields={"id"}, errorPath="username",
 *     entityClass="App\Entity\User", groups={"registration", "edit"})
 */
class UserData implements UserInterface, NormalizeMarkdownInterface {
    /**
     * @Groups({"user:read", "abbreviated_relations"})
     *
     * @var int|null
     */
    private $id;

    /**
     * @Assert\Length(min=3, max=25, groups={"registration", "edit"})
     * @Assert\NotBlank(groups={"registration", "edit"})
     * @Assert\Regex("/^\w+$/", groups={"registration", "edit"})
     *
     * @Groups({"user:read", "abbreviated_relations"})
     *
     * @var string|null
     */
    private $username;

    /**
     * @var string|null
     */
    private $normalizedUsername;

    /**
     * @var string|null
     */
    private $password;

    /**
     * @Assert\Length(min=8, max=72, charset="8bit", groups={"registration", "edit"})
     *
     * @var string|null
     */
    private $plainPassword;

    /**
     * @Assert\Email(groups={"registration", "edit"})
     *
     * @var string|null
     */
    private $email;

    /**
     * @Groups({"user:read"})
     *
     * @var \DateTime|null
     */
    private $created;

    /**
     * @Groups("user:preferences")
     *
     * @var string|null
     */
    private $locale;

    /**
     * @Groups("user:preferences")
     *
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @Assert\Choice(Submission::FRONT_PAGE_OPTIONS, groups={"settings"}, strict=true)
     * @Assert\NotBlank(groups={"settings"})
     *
     * @Groups("user:preferences")
     *
     * @var string|null
     */
    private $frontPage;

    /**
     * @Assert\Choice({Submission::SORT_ACTIVE, Submission::SORT_HOT, Submission::SORT_NEW}, groups={"settings"}, strict=true)
     * @Assert\NotBlank(groups={"settings"})
     *
     * @Groups("user:preferences")
     *
     * @var string|null
     */
    private $frontPageSortMode;

    /**
     * @Groups("user:preferences")
     *
     * @var bool|null
     */
    private $showCustomStylesheets;

    /**
     * @Groups("user:preferences")
     *
     * @var Theme|null
     */
    private $preferredTheme;

    /**
     * @Groups("user:preferences")
     *
     * @var bool|null
     */
    private $openExternalLinksInNewTab;

    /**
     * @Assert\Length(max=300, groups={"edit_biography"})
     *
     * @Groups({"user:read"})
     *
     * @var string|null
     */
    private $biography;

    /**
     * @Groups("user:preferences")
     *
     * @var bool|null
     */
    private $autoFetchSubmissionTitles;

    /**
     * @Groups("user:preferences")
     *
     * @var bool|null
     */
    private $enablePostPreviews;

    /**
     * @Groups("user:preferences")
     *
     * @var bool|null
     */
    private $showThumbnails;

    /**
     * @Groups("user:preferences")
     *
     * @var bool|null
     */
    private $allowPrivateMessages;

    /**
     * @Groups("user:preferences")
     *
     * @var bool|null
     */
    private $notifyOnReply;

    /**
     * @Groups("user:preferences")
     *
     * @var bool|null
     */
    private $notifyOnMentions;

    /**
     * @Assert\Length(max=200, groups={"settings"})
     *
     * @Groups("user:preferences")
     *
     * @var string|null
     */
    private $preferredFonts;

    /**
     * @Groups("user:preferences")
     *
     * @var bool|null
     */
    private $poppersEnabled;

    /**
     * @Groups({"user:read"})
     *
     * @var bool
     */
    private $admin = false;

    public function __construct(User $user = null) {
        if ($user) {
            $this->id = $user->getId();
            $this->username = $user->getUsername();
            $this->email = $user->getEmail();
            $this->created = $user->getCreated();
            $this->admin = $user->isAdmin();
            $this->locale = $user->getLocale();
            $this->timezone = $user->getTimezone();
            $this->frontPage = $user->getFrontPage();
            $this->frontPageSortMode = $user->getFrontPageSortMode();
            $this->showCustomStylesheets = $user->isShowCustomStylesheets();
            $this->preferredTheme = $user->getPreferredTheme();
            $this->openExternalLinksInNewTab = $user->openExternalLinksInNewTab();
            $this->biography = $user->getBiography();
            $this->autoFetchSubmissionTitles = $user->autoFetchSubmissionTitles();
            $this->enablePostPreviews = $user->enablePostPreviews();
            $this->showThumbnails = $user->showThumbnails();
            $this->allowPrivateMessages = $user->allowPrivateMessages();
            $this->notifyOnReply = $user->getNotifyOnReply();
            $this->notifyOnMentions = $user->getNotifyOnMentions();
            $this->preferredFonts = $user->getPreferredFonts();
            $this->poppersEnabled = $user->isPoppersEnabled();
        }
    }

    public function updateUser(User $user): void {
        $user->setUsername($this->username);

        if ($this->password) {
            $user->setPassword($this->password);
        }

        $user->setEmail($this->email);
        $user->setLocale($this->locale);
        $user->setTimezone($this->timezone);
        $user->setFrontPage($this->frontPage);
        $user->setFrontPageSortMode($this->frontPageSortMode);
        $user->setShowCustomStylesheets($this->showCustomStylesheets);
        $user->setPreferredTheme($this->preferredTheme);
        $user->setOpenExternalLinksInNewTab($this->openExternalLinksInNewTab);
        $user->setBiography($this->biography);
        $user->setAutoFetchSubmissionTitles($this->autoFetchSubmissionTitles);
        $user->setEnablePostPreviews($this->enablePostPreviews);
        $user->setShowThumbnails($this->showThumbnails);
        $user->setAllowPrivateMessages($this->allowPrivateMessages);
        $user->setNotifyOnReply($this->notifyOnReply);
        $user->setNotifyOnMentions($this->notifyOnMentions);
        $user->setPreferredFonts($this->preferredFonts);
        $user->setPoppersEnabled($this->poppersEnabled);
        $user->setAdmin($this->admin);
    }

    public function toUser(?string $ip): User {
        $user = new User($this->username, $this->password);
        $user->setEmail($this->email);
        $user->setBiography($this->biography);
        $user->setAdmin($this->admin);
        $user->setRegistrationIp($ip);

        $settings = [
            'showCustomStylesheets',
            'frontPage',
            'frontPageSortMode',
            'locale',
            'timezone',
            'preferredTheme',
            'openExternalLinksInNewTab',
            'autoFetchSubmissionTitles',
            'enablePostPreviews',
            'showThumbnails',
            'allowPrivateMessages',
            'notifyOnReply',
            'notifyOnMentions',
            'preferredFonts',
            'poppersEnabled',
        ];

        foreach ($settings as $setting) {
            if ($this->{$setting} !== null) {
                $user->{'set'.ucfirst($setting)}($this->{$setting});
            }
        }

        return $user;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getUsername(): ?string {
        return $this->username;
    }

    public function setUsername(?string $username): void {
        $this->username = $username;
        $this->normalizedUsername = isset($username) ? User::normalizeUsername($username) : null;
    }

    public function getNormalizedUsername(): ?string {
        return $this->normalizedUsername;
    }

    public function getPassword(): ?string {
        return $this->password;
    }

    public function setPassword(?string $password): void {
        $this->password = $password;
    }

    public function getPlainPassword(): ?string {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): void {
        $this->plainPassword = $plainPassword;
    }

    public function getEmail() {
        return $this->email;
    }

    public function setEmail(?string $email): void {
        $this->email = $email;
    }

    public function getCreated(): ?\DateTime {
        return $this->created;
    }

    public function getLocale() {
        return $this->locale;
    }

    public function setLocale(?string $locale): void {
        $this->locale = $locale;
    }

    public function getTimezone(): \DateTimeZone {
        return $this->timezone;
    }

    public function setTimezone(\DateTimeZone $timezone): void {
        $this->timezone = $timezone;
    }

    public function getFrontPage() {
        return $this->frontPage;
    }

    public function setFrontPage(?string $frontPage): void {
        $this->frontPage = $frontPage;
    }

    public function getFrontPageSortMode(): ?string {
        return $this->frontPageSortMode;
    }

    public function setFrontPageSortMode(?string $frontPageSortMode): void {
        $this->frontPageSortMode = $frontPageSortMode;
    }

    public function getShowCustomStylesheets() {
        return $this->showCustomStylesheets;
    }

    public function setShowCustomStylesheets(bool $showCustomStylesheets): void {
        $this->showCustomStylesheets = $showCustomStylesheets;
    }

    public function getPreferredTheme(): ?Theme {
        return $this->preferredTheme;
    }

    public function setPreferredTheme(?Theme $preferredTheme): void {
        $this->preferredTheme = $preferredTheme;
    }

    public function openExternalLinksInNewTab(): ?bool {
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

    public function getAutoFetchSubmissionTitles(): ?bool {
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

    public function showThumbnails(): ?bool {
        return $this->showThumbnails;
    }

    public function setShowThumbnails(bool $showThumbnails): void {
        $this->showThumbnails = $showThumbnails;
    }

    public function allowPrivateMessages(): ?bool {
        return $this->allowPrivateMessages;
    }

    public function setAllowPrivateMessages(bool $allowPrivateMessages): void {
        $this->allowPrivateMessages = $allowPrivateMessages;
    }

    public function getNotifyOnReply(): ?bool {
        return $this->notifyOnReply;
    }

    public function setNotifyOnReply(bool $notifyOnReply): void {
        $this->notifyOnReply = $notifyOnReply;
    }

    public function getNotifyOnMentions(): ?bool {
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

    public function isPoppersEnabled(): ?bool {
        return $this->poppersEnabled;
    }

    public function setPoppersEnabled(?bool $poppersEnabled): void {
        $this->poppersEnabled = $poppersEnabled;
    }

    public function isAdmin(): bool {
        return $this->admin;
    }

    public function setAdmin(bool $admin): void {
        $this->admin = $admin;
    }

    public function getRoles(): array {
        return [];
    }

    public function getSalt(): ?string {
        return null;
    }

    public function eraseCredentials(): void {
        $this->plainPassword = null;
    }

    public function getMarkdownFields(): iterable {
        yield 'biography';
    }
}
