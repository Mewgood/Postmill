<?php

namespace App\Security\Voter;

use App\Entity\Submission;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class SubmissionVoter extends Voter {
    public const ATTRIBUTES = [
        'delete_own',
        'edit',
        'lock',
        'mod_delete',
        'pin',
        'purge',
    ];

    protected function supports(string $attribute, $subject): bool {
        return $subject instanceof Submission && \in_array($attribute, self::ATTRIBUTES, true);
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
        case 'lock':
            return $this->canLock($subject, $token);
        case 'mod_delete':
            return $this->canModDelete($subject, $token);
        case 'pin':
            return $this->canPin($subject, $token);
        case 'purge':
            return $this->canPurge($subject, $token);
        default:
            throw new \RuntimeException("Invalid attribute '$attribute'");
        }
    }

    private function canDeleteOwn(Submission $submission, TokenInterface $token): bool {
        if ($submission->getVisibility() === Submission::VISIBILITY_DELETED) {
            return false;
        }

        if ($submission->getUser() !== $token->getUser()) {
            return false;
        }

        return true;
    }

    private function canModDelete(Submission $submission, TokenInterface $token): bool {
        if ($submission->getVisibility() === Submission::VISIBILITY_DELETED) {
            return false;
        }

        if ($submission->getUser() === $token->getUser()) {
            return false;
        }

        if (!$submission->getForum()->userIsModerator($token->getUser())) {
            return false;
        }

        return true;
    }

    private function canPurge(Submission $submission, TokenInterface $token): bool {
        if ($submission->getCommentCount() === 0) {
            return false;
        }

        if (!$submission->getForum()->userIsModerator($token->getUser())) {
            return false;
        }

        return true;
    }

    private function canEdit(Submission $submission, TokenInterface $token): bool {
        if ($submission->getVisibility() === Submission::VISIBILITY_DELETED) {
            return false;
        }

        if ($token->getUser()->isAdmin()) {
            return true;
        }

        if ($submission->getUser() !== $token->getUser()) {
            return false;
        }

        if ($submission->isModerated()) {
            return false;
        }

        return true;
    }

    private function canPin(Submission $submission, TokenInterface $token): bool {
        if ($submission->getVisibility() === Submission::VISIBILITY_DELETED) {
            return false;
        }

        return $submission->getForum()->userIsModerator($token->getUser());
    }

    private function canLock(Submission $submission, TokenInterface $token): bool {
        return $submission->getForum()->userIsModerator($token->getUser());
    }
}
