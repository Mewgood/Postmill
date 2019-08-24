<?php

namespace App\Message;

use App\Entity\User;

class DeleteUser {
    /**
     * @var int
     */
    private $userId;

    /**
     * @param User|int|mixed $user
     */
    public function __construct($user) {
        if ($user instanceof User) {
            if ($user->getId() === null) {
                throw new \InvalidArgumentException('The given user must have an ID');
            }

            $this->userId = $user->getId();
        } elseif (is_scalar($user)) {
            $this->userId = $user;
        } else {
            throw new \InvalidArgumentException(sprintf(
                '$user must be integer or instance of %s, %s given',
                User::class,
                \is_object($user) ? \get_class($user) : \gettype($user)
            ));
        }
    }

    public function getUserId(): int {
        return $this->userId;
    }
}
