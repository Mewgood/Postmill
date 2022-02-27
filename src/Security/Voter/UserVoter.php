<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Repository\ModeratorRepository;
use App\Repository\SiteRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class UserVoter extends Voter {
    public const ATTRIBUTES = ['edit_biography', 'edit_user', 'edit_username', 'message'];

    /**
     * @var AccessDecisionManagerInterface
     */
    private $decisionManager;

    /**
     * @var ModeratorRepository
     */
    private $moderators;

    /**
     * @var SiteRepository
     */
    private $siteRepository;

    public function __construct(
        AccessDecisionManagerInterface $decisionManager,
        ModeratorRepository $moderators,
        SiteRepository $siteRepository
    ) {
        $this->decisionManager = $decisionManager;
        $this->moderators = $moderators;
        $this->siteRepository = $siteRepository;
    }

    protected function supports(string $attribute, $subject): bool {
        return $subject instanceof User && \in_array($attribute, self::ATTRIBUTES, true);
    }

    public function supportsAttribute(string $attribute): bool {
        return \in_array($attribute, self::ATTRIBUTES, true);
    }

    public function supportsType(string $subjectType): bool {
        return is_a($subjectType, User::class, true);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool {
        if (!$subject instanceof User) {
            throw new \InvalidArgumentException('$subject must be '.User::class);
        }

        switch ($attribute) {
        case 'edit_biography':
            return $this->canEditBiography($subject, $token);
        case 'edit_user':
            return $this->canEditUser($subject, $token);
        case 'edit_username':
            return $this->canEditUsername($subject, $token);
        case 'message':
            return $this->canMessage($subject, $token);
        default:
            throw new \InvalidArgumentException("Unknown attribute '$attribute'");
        }
    }

    private function canEditBiography(User $user, TokenInterface $token): bool {
        if ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        if (!$token->getUser() instanceof User) {
            return false;
        }

        if ($user !== $token->getUser()) {
            return false;
        }

        if ($this->decisionManager->decide($token, ['ROLE_WHITELISTED'])) {
            return true;
        }

        return $user->getSubmissionCount() > 0 || $user->getCommentCount() > 0;
    }

    private function canEditUser(User $user, TokenInterface $token): bool {
        if ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        if (!$token->getUser() instanceof User) {
            return false;
        }

        return $user === $token->getUser();
    }

    private function canEditUsername(User $user, TokenInterface $token): bool {
        if ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        if (!$token->getUser() instanceof User) {
            return false;
        }

        $site = $this->siteRepository->findCurrentSite();

        return $user === $token->getUser() && $site->isUsernameChangeEnabled();
    }

    private function canMessage(User $receiver, TokenInterface $token): bool {
        if ($receiver->isAccountDeleted()) {
            return false;
        }

        if ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        $sender = $token->getUser();

        if (!$sender instanceof User) {
            return false;
        }

        $site = $this->siteRepository->findCurrentSite();

        if (
            !$sender->isWhitelisted() &&
            !$site->isUnwhitelistedUserMessagesEnabled()
        ) {
            return false;
        }

        if ($receiver->isBlocking($sender) || $sender->isBlocking($receiver)) {
            return false;
        }

        if (
            !$receiver->allowPrivateMessages() &&
            !$this->moderators->userRulesOverSubject($sender, $receiver)
        ) {
            return false;
        }

        return true;
    }
}
