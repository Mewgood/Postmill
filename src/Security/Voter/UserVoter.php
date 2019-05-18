<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Repository\ModeratorRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class UserVoter extends Voter {
    const ATTRIBUTES = ['edit_user', 'message'];

    /**
     * @var AccessDecisionManagerInterface
     */
    private $decisionManager;

    /**
     * @var ModeratorRepository
     */
    private $moderators;

    public function __construct(
        AccessDecisionManagerInterface $decisionManager,
        ModeratorRepository $moderators
    ) {
        $this->decisionManager = $decisionManager;
        $this->moderators = $moderators;
    }

    protected function supports($attribute, $subject): bool {
        return $subject instanceof User && \in_array($attribute, self::ATTRIBUTES, true);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool {
        if (!$subject instanceof User) {
            throw new \InvalidArgumentException('$subject must be '.User::class);
        }

        switch ($attribute) {
        case 'edit_user':
            return $this->canEditUser($subject, $token);
        case 'message':
            return $this->canMessage($subject, $token);
        default:
            throw new \InvalidArgumentException("Unknown attribute '$attribute'");
        }
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

    private function canMessage(User $receiver, TokenInterface $token): bool {
        if ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        $sender = $token->getUser();

        if (!$sender instanceof User) {
            return false;
        }

        if ($receiver->isBlocking($sender)) {
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
