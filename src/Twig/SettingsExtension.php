<?php

namespace App\Twig;

use App\Entity\User;
use App\Repository\SiteRepository;
use Symfony\Component\Security\Core\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Make settings accessible to templates.
 *
 * @todo extend with more settings
 */
class SettingsExtension extends AbstractExtension {
    /**
     * @var SiteRepository
     */
    private $sites;

    /**
     * @var Security
     */
    private $security;

    public function __construct(Security $security, SiteRepository $sites) {
        $this->security = $security;
        $this->sites = $sites;
    }

    public function getFunctions(): array {
        return [
            new TwigFunction('submission_link_destination', [$this, 'getSubmissionLinkDestination']),
        ];
    }

    public function getSubmissionLinkDestination(): string {
        $user = $this->security->getUser();
        \assert($user instanceof User || $user === null);

        if ($user) {
            $destination = $user->getSubmissionLinkDestination();
        }

        if (!isset($destination)) {
            $destination = $this->sites
                ->findCurrentSite()
                ->getSubmissionLinkDestination();
        }

        return $destination;
    }
}
