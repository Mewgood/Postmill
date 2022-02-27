<?php

namespace App\Security\Voter;

use App\Entity\Forum;
use App\Entity\User;
use App\Repository\SiteRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class ForumVoter extends Voter {
    public const ATTRIBUTES = [
        'moderator',
        'delete',
        'view_moderation_log',
        'set_log_visibility',
    ];

    /**
     * @var SiteRepository
     */
    private $sites;

    public function __construct(SiteRepository $siteRepository) {
        $this->sites = $siteRepository;
    }

    protected function supports(string $attribute, $subject): bool {
        return $subject instanceof Forum && \in_array($attribute, self::ATTRIBUTES, true);
    }

    public function supportsAttribute(string $attribute): bool {
        return \in_array($attribute, self::ATTRIBUTES, true);
    }

    public function supportsType(string $subjectType): bool {
        return is_a($subjectType, Forum::class, true);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool {
        if (!$subject instanceof Forum) {
            throw new \InvalidArgumentException('$subject must be '.Forum::class);
        }

        if ($attribute == 'view_moderation_log' && $subject->isModerationLogPublic()) {
            return true;
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        switch ($attribute) {
        case 'moderator':
            return $subject->userIsModerator($user);
        case 'view_moderation_log':
            return $subject->userIsModerator($user, true);
        case 'set_log_visibility':
            return $this->canSetLogVisibility($user, $subject);
        case 'delete':
            return $subject->userCanDelete($user);
        default:
            throw new \InvalidArgumentException('Bad attribute '.$attribute);
        }
    }

    private function canSetLogVisibility(User $user, Forum $forum): bool {
        if ($user->isAdmin()) {
            return true;
        }

        if (!$forum->userIsModerator($user)) {
            return false;
        }

        return $this->sites
            ->findCurrentSite()
            ->getModeratorsCanSetForumLogVisibility();
    }
}
