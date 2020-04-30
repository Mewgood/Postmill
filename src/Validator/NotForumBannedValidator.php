<?php

namespace App\Validator;

use App\Entity\Forum;
use App\Entity\User;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class NotForumBannedValidator extends ConstraintValidator {
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage) {
        $this->tokenStorage = $tokenStorage;
    }

    public function validate($value, Constraint $constraint): void {
        if (!$value || !$this->tokenStorage->getToken() || !$this->tokenStorage->getToken()->getUser()) {
            return;
        }

        if (!\is_object($value)) {
            throw new UnexpectedTypeException($value, 'object');
        }

        if (!$constraint instanceof NotForumBanned) {
            throw new UnexpectedTypeException($constraint, NotForumBanned::class);
        }

        if ($constraint->forumPath) {
            $propertyAccessor = PropertyAccess::createPropertyAccessor();

            if (!$propertyAccessor->isReadable($value, $constraint->forumPath)) {
                throw new InvalidArgumentException(sprintf(
                    'Cannot read property %s on object of type %s',
                    $constraint->forumPath,
                    \get_class($value)
                ));
            }

            $forum = $propertyAccessor->getValue($value, $constraint->forumPath);
        } else {
            $forum = $value;
        }

        if ($forum === null) {
            return;
        }

        if (!$forum instanceof Forum) {
            throw new InvalidArgumentException(sprintf(
                'Property %s on object of type %s is not of type %s',
                $constraint->forumPath,
                \get_class($value),
                Forum::class
            ));
        }

        $token = $this->tokenStorage->getToken();
        $user = $token ? $token->getUser() : null;

        if (!$token || !$user instanceof User) {
            return;
        }

        if ($forum->userIsBanned($user)) {
            $this->context->buildViolation($constraint->message)
                ->setCode(NotForumBanned::FORUM_BANNED_ERROR)
                ->atPath($constraint->errorPath)
                ->addViolation();
        }
    }
}
