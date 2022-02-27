<?php

namespace App\Security\Voter;

use App\Entity\WikiRevision;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class WikiRevisionVoter extends Voter {
    public const ATTRIBUTES = ['view'];

    /**
     * @var AccessDecisionManagerInterface
     */
    private $decisionManager;

    public function __construct(
        AccessDecisionManagerInterface $decisionManager
    ) {
        $this->decisionManager = $decisionManager;
    }

    protected function supports(string $attribute, $subject): bool {
        return $subject instanceof WikiRevision &&
            \in_array($attribute, self::ATTRIBUTES, true);
    }

    public function supportsAttribute(string $attribute): bool {
        return \in_array($attribute, self::ATTRIBUTES, true);
    }

    public function supportsType(string $subjectType): bool {
        return is_a($subjectType, WikiRevision::class, true);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool {
        \assert($subject instanceof WikiRevision);

        switch ($attribute) {
        case 'view':
            return $this->decisionManager->decide($token, ['view_log'], $subject->getPage());
        default:
            throw new \LogicException("Unknown attribute '$attribute'");
        }
    }

}
