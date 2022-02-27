<?php

namespace App\Security\Voter;

use App\Entity\Moderator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class ModeratorVoter extends Voter {
    protected function supports(string $attribute, $subject): bool {
        return $attribute === 'remove' && $subject instanceof Moderator;
    }

    public function supportsAttribute(string $attribute): bool{
        return $attribute === 'remove';
    }

    public function supportsType(string $subjectType): bool {
        return is_a($subjectType, Moderator::class, true);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool {
        if (!$subject instanceof Moderator) {
            throw new \InvalidArgumentException('$subject must be '.Moderator::class);
        }

        switch ($attribute) {
        case 'remove':
            return $subject->userCanRemove($token->getUser());
        default:
            throw new \InvalidArgumentException('Invalid attribute '.$attribute);
        }
    }
}
