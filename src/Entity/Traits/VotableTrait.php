<?php

namespace App\Entity\Traits;

use App\Entity\Contracts\VotableInterface;
use App\Entity\Exception\BadVoteChoiceException;
use App\Entity\User;
use App\Entity\Vote;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;

trait VotableTrait {
    abstract protected function createVote(int $choice, User $user, ?string $ip): Vote;

    /**
     * @return Collection|Selectable|Vote[]
     */
    abstract protected function getVotes(): Collection;

    public function vote(int $choice, User $user, ?string $ip): void {
        if (!\in_array($choice, [
            VotableInterface::VOTE_UP,
            VotableInterface::VOTE_DOWN,
            VotableInterface::VOTE_NONE,
        ], true)) {
            throw new BadVoteChoiceException($choice);
        }

        $vote = $this->getUserVote($user);

        if ($vote && $choice !== $vote->getChoice()) {
            $this->getVotes()->removeElement($vote);
        }

        if ($choice !== VotableInterface::VOTE_NONE) {
            $this->getVotes()->add($this->createVote($choice, $user, $ip));
        }
    }

    public function getUserChoice(User $user): int {
        $vote = $this->getUserVote($user);

        return $vote ? $vote->getChoice() : VotableInterface::VOTE_NONE;
    }

    public function getUpvotes(): int {
        $this->getVotes()->get(-1); // hydrate collection

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('upvote', true));

        return \count($this->getVotes()->matching($criteria));
    }

    public function getDownvotes(): int {
        $this->getVotes()->get(-1); // hydrate collection

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('upvote', false));

        return \count($this->getVotes()->matching($criteria));
    }

    public function getNetScore(): int {
        return $this->getUpvotes() - $this->getDownvotes();
    }

    private function getUserVote(User $user): ?Vote {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('user', $user));

        return $this->getVotes()->matching($criteria)->first() ?: null;
    }
}
