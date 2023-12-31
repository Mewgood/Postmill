<?php

namespace App\Security\Voter;

use App\Entity\MessageThread;
use App\Entity\User;
use App\Repository\SiteRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class MessageThreadVoter extends Voter {
    public const ATTRIBUTES = ['access', 'reply'];

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
        return $subject instanceof MessageThread && \in_array($attribute, self::ATTRIBUTES, true);
    }

    public function supportsAttribute(string $attribute): bool {
        return \in_array($attribute, self::ATTRIBUTES, true);
    }

    public function supportsType(string $subjectType): bool {
        return is_a($subjectType, MessageThread::class, true);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool {
        if (!$subject instanceof MessageThread) {
            throw new \InvalidArgumentException('$subject must be '.MessageThread::class);
        }

        switch ($attribute) {
        case 'access':
            return $subject->userIsParticipant($token->getUser());
        case 'reply':
            return $this->canReply($subject, $token);
        default:
            throw new \InvalidArgumentException("Unknown attribute '$attribute'");
        }
    }

    private function canReply(MessageThread $thread, TokenInterface $token): bool {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if (!$thread->userIsParticipant($user)) {
            return false;
        }

        if ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        $site = $this->siteRepository->findCurrentSite();

        if (
            !$user->isWhitelisted() &&
            !$site->isUnwhitelistedUserMessagesEnabled()
        ) {
            return false;
        }

        $otherParticipants = $thread->getOtherParticipants($user);

        if (\count($otherParticipants) === 1 && (
            $otherParticipants[0]->isAccountDeleted() ||
            $otherParticipants[0]->isBlocking($user) ||
            $user->isBlocking($otherParticipants[0])
        )) {
            return false;
        }

        return true;
    }
}
