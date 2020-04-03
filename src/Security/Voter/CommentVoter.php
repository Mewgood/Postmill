<?php

namespace App\Security\Voter;

use App\Entity\Comment;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class CommentVoter extends Voter {
    public const ATTRIBUTES = ['delete_own', 'edit'];

    private $decisionManager;

    public function __construct(AccessDecisionManagerInterface $decisionManager) {
        $this->decisionManager = $decisionManager;
    }

    protected function supports(string $attribute, $subject): bool {
        return $subject instanceof Comment && \in_array($attribute, self::ATTRIBUTES, true);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool {
        if (!$token->getUser() instanceof User) {
            return false;
        }

        switch ($attribute) {
        case 'delete_own':
            return $this->canDeleteOwn($subject, $token);
        case 'edit':
            return $this->canEdit($subject, $token);
        default:
            throw new \InvalidArgumentException('Unknown attribute '.$attribute);
        }
    }

    private function canDeleteOwn(Comment $comment, TokenInterface $token): bool {
        if ($comment->getVisibility() === Comment::VISIBILITY_DELETED) {
            return false;
        }

        return $comment->getUser() === $token->getUser();
    }

    private function canEdit(Comment $comment, TokenInterface $token): bool {
        if ($comment->getVisibility() === Comment::VISIBILITY_DELETED) {
            return false;
        }

        if ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        if ($comment->isModerated()) {
            return false;
        }

        return $comment->getUser() === $token->getUser();
    }
}
