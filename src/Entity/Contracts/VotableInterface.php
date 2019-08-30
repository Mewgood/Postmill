<?php

namespace App\Entity\Contracts;

use App\Entity\Exception\BadVoteChoiceException;
use App\Entity\User;

interface VotableInterface {
    public const VOTE_UP = 1;
    public const VOTE_NONE = 0;
    public const VOTE_DOWN = -1;

    public function getDownvotes(): int;

    public function getNetScore(): int;

    public function getUpvotes(): int;

    public function getUserChoice(User $user): int;

    /**
     * @throws BadVoteChoiceException if $choice is bad
     */
    public function vote(int $choice, User $user, string $ip): void;
}
