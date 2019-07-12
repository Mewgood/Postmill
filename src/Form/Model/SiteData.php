<?php

namespace App\Form\Model;

use App\Entity\Site;
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
    public $imageUploadingAllowed;

    public static function createFromSite(Site $site): self {
        $self = new self();
        $self->siteName = $site->getSiteName();
        $self->imageUploadingAllowed = $site->isImageUploadingAllowed();

        return $self;
    }

    public function updateSite(Site $site): void {
        $site->setSiteName($this->siteName);
        $site->setImageUploadingAllowed($this->imageUploadingAllowed);
    }
}
