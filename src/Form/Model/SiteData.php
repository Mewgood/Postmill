<?php

namespace App\Form\Model;

use App\Entity\Constants\SubmissionLinkDestination;
use App\Entity\Site;
use App\Entity\Submission;
use App\Entity\Theme;
use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

class SiteData {
    /**
     * @Assert\NotBlank()
     * @Assert\Length(min=1, max=60)
     *
     * @var string
     */
    public $siteName;

    /**
     * @var bool
     */
    public $registrationOpen;

    /**
     * @var bool
     */
    public $usernameChangeEnabled;

    /**
     * @var bool
     */
    public $unwhitelistedUserMessagesEnabled;

    /**
     * @Assert\Choice({Submission::SORT_HOT, Submission::SORT_ACTIVE, Submission::SORT_NEW})
     * @Assert\NotBlank()
     *
     * @var string
     */
    public $defaultSortMode;

    /**
     * @var Theme|null
     */
    public $defaultTheme;

    /**
     * @var bool
     */
    public $wikiEnabled;

    /**
     * @var bool
     */
    public $wikiLogPublic;

    /**
     * @Assert\Choice(User::ROLES)
     * @Assert\NotBlank()
     *
     * @var string
     */
    public $forumCreateRole;

    /**
     * @var bool
     */
    public $moderatorsCanSetForumLogVisibility;

    /**
     * @Assert\Choice(User::ROLES)
     * @Assert\NotBlank()
     *
     * @var string
     */
    public $imageUploadRole;

    /**
     * @Assert\Choice(User::ROLES)
     * @Assert\NotBlank()
     *
     * @var string
     */
    public $wikiEditRole;

    /**
     * @var bool
     */
    public $trashEnabled;

    /**
     * @var bool
     */
    public $registrationCaptchaEnabled;

    /**
     * @var bool
     */
    public $urlImagesEnabled;

    /**
     * @Assert\Choice(SubmissionLinkDestination::OPTIONS)
     * @Assert\NotBlank()
     *
     * @var string
     */
    public $submissionLinkDestination;

    public static function createFromSite(Site $site): self {
        $self = new self();
        $self->siteName = $site->getSiteName();
        $self->registrationOpen = $site->isRegistrationOpen();
        $self->usernameChangeEnabled = $site->isUsernameChangeEnabled();
        $self->unwhitelistedUserMessagesEnabled = $site->isUnwhitelistedUserMessagesEnabled();
        $self->defaultSortMode = $site->getDefaultSortMode();
        $self->defaultTheme = $site->getDefaultTheme();
        $self->wikiEnabled = $site->isWikiEnabled();
        $self->wikiLogPublic = $site->isWikiLogPublic();
        $self->forumCreateRole = $site->getForumCreateRole();
        $self->moderatorsCanSetForumLogVisibility = $site->getModeratorsCanSetForumLogVisibility();
        $self->imageUploadRole = $site->getImageUploadRole();
        $self->wikiEditRole = $site->getWikiEditRole();
        $self->trashEnabled = $site->isTrashEnabled();
        $self->registrationCaptchaEnabled = $site->isRegistrationCaptchaEnabled();
        $self->urlImagesEnabled = $site->isUrlImagesEnabled();
        $self->submissionLinkDestination = $site->getSubmissionLinkDestination();

        return $self;
    }

    public function updateSite(Site $site): void {
        $site->setSiteName($this->siteName);
        $site->setRegistrationOpen($this->registrationOpen);
        $site->setUsernameChangeEnabled($this->usernameChangeEnabled);
        $site->setUnwhitelistedUserMessagesEnabled($this->unwhitelistedUserMessagesEnabled);
        $site->setDefaultSortMode($this->defaultSortMode);
        $site->setDefaultTheme($this->defaultTheme);
        $site->setWikiEnabled($this->wikiEnabled);
        $site->setWikiLogPublic($this->wikiLogPublic);
        $site->setForumCreateRole($this->forumCreateRole);
        $site->setModeratorsCanSetForumLogVisibility($this->moderatorsCanSetForumLogVisibility);
        $site->setImageUploadRole($this->imageUploadRole);
        $site->setWikiEditRole($this->wikiEditRole);
        $site->setTrashEnabled($this->trashEnabled);
        $site->setRegistrationCaptchaEnabled($this->registrationCaptchaEnabled);
        $site->setUrlImagesEnabled($this->urlImagesEnabled);
        $site->setSubmissionLinkDestination($this->submissionLinkDestination);
    }
}
