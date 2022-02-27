<?php

namespace App\Security\Voter;

use App\Entity\Site;
use App\Entity\User;
use App\Repository\SiteRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class SiteVoter extends Voter {
    public const ATTRIBUTES = [
        self::REGISTER,
        self::CREATE_FORUM,
        self::UPLOAD_IMAGE,
        self::VIEW_WIKI,
        self::VIEW_WIKI_LOG
    ];

    public const REGISTER = 'register';
    public const CREATE_FORUM = 'create_forum';
    public const UPLOAD_IMAGE = 'upload_image';
    public const VIEW_WIKI = 'view_wiki';
    public const VIEW_WIKI_LOG = 'view_wiki_log';

    /**
     * @var AccessDecisionManagerInterface
     */
    private $decisionManager;

    /**
     * @var SiteRepository
     */
    private $siteRepository;

    public function __construct(
        AccessDecisionManagerInterface $decisionManager,
        SiteRepository $siteRepository
    ) {
        $this->decisionManager = $decisionManager;
        $this->siteRepository = $siteRepository;
    }

    protected function supports(string $attribute, $subject): bool {
        return ($subject instanceof Site || $subject === null) &&
            \in_array($attribute, self::ATTRIBUTES, true);
    }

    public function supportsAttribute(string $attribute): bool {
        return \in_array($attribute, self::ATTRIBUTES, true);
    }

    public function supportsType(string $subjectType): bool {
        return $subjectType === 'null' || is_a($subjectType, Site::class, true);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool {
        if (!$subject) {
            $subject = $this->siteRepository->findCurrentSite();

            \assert($subject instanceof Site);
        }

        switch ($attribute) {
        case self::CREATE_FORUM:
            return $this->decide($token, $subject->getForumCreateRole());
        case self::UPLOAD_IMAGE:
            return $this->decide($token, $subject->getImageUploadRole());
        case self::VIEW_WIKI:
            return $subject->isWikiEnabled();
        case self::VIEW_WIKI_LOG:
            return $this->canViewWikiLog($token, $subject);
        case self::REGISTER:
            return $subject->isRegistrationOpen();
        default:
            throw new \InvalidArgumentException("Unknown attribute '$attribute'");
        }
    }

    private function decide(TokenInterface $token, string $role): bool {
        return $this->decisionManager->decide($token, [$role]);
    }

    private function canViewWikiLog(TokenInterface $token, Site $site): bool {
        if ($site->isWikiLogPublic()) {
            return true;
        }

        if (!$token->getUser() instanceof User) {
            return false;
        }

        if ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        return $this->decisionManager->decide($token, [$site->getWikiEditRole()]);
    }
}
