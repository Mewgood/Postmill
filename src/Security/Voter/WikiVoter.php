<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Entity\WikiPage;
use App\Repository\SiteRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class WikiVoter extends Voter {
    public const ATTR_CREATE = 'create_wiki_page';
    public const ATTR_EDIT = 'edit';
    public const ATTR_DELETE = 'delete';
    public const ATTR_LOCK = 'lock';
    public const ATTR_VIEW_LOG = 'view_log';

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
        if ($subject === null && $attribute === self::ATTR_CREATE) {
            return true;
        }

        return $subject instanceof WikiPage && \in_array($attribute, [
            self::ATTR_EDIT,
            self::ATTR_DELETE,
            self::ATTR_LOCK,
            self::ATTR_VIEW_LOG,
        ], true);
    }

    public function supportsAttribute(string $attribute): bool {
        return in_array($attribute, [
            self::ATTR_CREATE,
            self::ATTR_EDIT,
            self::ATTR_DELETE,
            self::ATTR_LOCK,
            self::ATTR_VIEW_LOG,
        ], true);
    }

    public function supportsType(string $subjectType): bool {
        return $subjectType === 'null' ||
            is_a($subjectType, WikiPage::class, true);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool {
        if ($attribute === self::ATTR_VIEW_LOG) {
            $site = $this->siteRepository->findCurrentSite();
            return $this->decisionManager->decide($token, ['view_wiki_log'], $site);
        }

        switch ($attribute) {
        case self::ATTR_CREATE:
            return $this->canCreate($token);
        case self::ATTR_EDIT:
            return $this->canEdit($subject, $token);
        case self::ATTR_DELETE:
        case self::ATTR_LOCK:
            // todo: make this configurable
            return $this->decisionManager->decide($token, ['ROLE_ADMIN']);
        default:
            throw new \LogicException("Unknown attribute '$attribute'");
        }
    }

    private function canCreate(TokenInterface $token): bool {
        if (!$token->getUser() instanceof User) {
            return false;
        }

        $wikiEditRole = $this->siteRepository
            ->findCurrentSite()
            ->getWikiEditRole();

        return $this->decisionManager->decide($token, [$wikiEditRole]);
    }

    private function canEdit(WikiPage $page, TokenInterface $token): bool {
        if (!$token->getUser() instanceof User) {
            return false;
        }

        if ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        if ($page->isLocked()) {
            return false;
        }

        $site = $this->siteRepository->findCurrentSite();

        return $this->decisionManager->decide($token, [$site->getWikiEditRole()]);
    }
}
