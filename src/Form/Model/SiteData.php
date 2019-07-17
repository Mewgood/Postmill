<?php

namespace App\Form\Model;

use App\Entity\Site;
use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

final class SiteData {
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
    public $wikiEnabled;

    /**
     * @Assert\Choice(User::ROLES, strict=true)
     * @Assert\NotBlank()
     *
     * @var string
     */
    public $forumCreateRole;

    /**
     * @Assert\Choice(User::ROLES, strict=true)
     * @Assert\NotBlank()
     *
     * @var string
     */
    public $imageUploadRole;

    /**
     * @Assert\Choice(User::ROLES, strict=true)
     * @Assert\NotBlank()
     *
     * @var string
     */
    public $wikiEditRole;

    public static function createFromSite(Site $site): self {
        $self = new self();
        $self->siteName = $site->getSiteName();
        $self->wikiEnabled = $site->isWikiEnabled();
        $self->forumCreateRole = $site->getForumCreateRole();
        $self->imageUploadRole = $site->getImageUploadRole();
        $self->wikiEditRole = $site->getWikiEditRole();

        return $self;
    }

    public function updateSite(Site $site): void {
        $site->setSiteName($this->siteName);
        $site->setWikiEnabled($this->wikiEnabled);
        $site->setForumCreateRole($this->forumCreateRole);
        $site->setImageUploadRole($this->imageUploadRole);
        $site->setWikiEditRole($this->wikiEditRole);
    }
}
