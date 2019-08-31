<?php

namespace App\Security\Voter;

use App\Entity\Comment;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class CommentVoter extends Voter {
    /**
     * - delete_thread - Ability to delete a comment with its replies.
     * - softdelete - Ability to soft delete a comment.
     * - edit - Ability to edit a comment.
     */
    public const ATTRIBUTES = ['delete_thread', 'softdelete', 'edit'];

    private $decisionManager;

    public function __construct(AccessDecisionManagerInterface $decisionManager) {
        $this->decisionManager = $decisionManager;
    }

    protected function supports($attribute, $subject) {
        return \in_array($attribute, self::ATTRIBUTES, true) && $subject instanceof Comment;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token) {
        if (!$token->getUser() instanceof User) {
            return false;
        }

        switch ($attribute) {
        case 'delete_thread':
            return $this->canDeleteThread($subject, $token);
        case 'softdelete':
            return $this->canSoftDelete($subject, $token);
        case 'edit':
            return $this->canEdit($subject, $token);
        default:
            throw new \InvalidArgumentException('Unknown attribute '.$attribute);
        }
    }

    private function canDeleteThread(Comment $comment, TokenInterface $token): bool {
        $forum = $comment->getSubmission()->getForum();

        if ($forum->userIsModerator($token->getUser())) {
            return true;
        }

        if ($token->getUser() !== $comment->getUser()) {
            return false;
        }

        if (\count($comment->getChildren()) > 0) {
            return false;
        }

        return true;
    }

    private function canSoftDelete(Comment $comment, TokenInterface $token): bool {
        if ($comment->getVisibility() !== Comment::VISIBILITY_VISIBLE) {
            return false;
        }

        if (\count($comment->getChildren()) === 0) {
            return false;
        }

        if ($token->getUser() === $comment->getUser()) {
            return true;
        }

        $forum = $comment->getSubmission()->getForum();

        if (!$forum->userIsModerator($token->getUser())) {
            return false;
        }

        return true;
    }

    private function canEdit(Comment $comment, TokenInterface $token): bool {
        if ($comment->getVisibility() !== Comment::VISIBILITY_VISIBLE) {
            return false;
        }

        if ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        if ($token->getUser() !== $comment->getUser()) {
            return false;
        }

        if ($comment->isModerated()) {
            return false;
        }

        return true;
    }
}
