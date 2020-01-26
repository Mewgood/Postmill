<?php

namespace App\Tests\Entity\Traits;

use App\Entity\Contracts\VotableInterface;
use App\Entity\Traits\VotableTrait;
use App\Entity\User;
use App\Entity\Vote;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class VotableTraitTest extends TestCase {
    /**
     * @var VotableTrait
     */
    private $votable;

    protected function setUp(): void {
        $this->votable = $this->createVotable();
    }

    public function testVotableScores(): void {
        $votable = $this->createVotable();

        $user = new User('u', 'p');

        $this->assertEquals(0, $votable->getNetScore());
        $this->assertEquals(0, $votable->getUpvotes());
        $this->assertEquals(0, $votable->getDownvotes());

        $votable->vote(VotableInterface::VOTE_UP, $user, null);

        $this->assertEquals(1, $votable->getNetScore());
        $this->assertEquals(1, $votable->getUpvotes());
        $this->assertEquals(0, $votable->getDownvotes());

        $votable->vote(VotableInterface::VOTE_DOWN, $user, null);

        $this->assertEquals(-1, $votable->getNetScore());
        $this->assertEquals(0, $votable->getUpvotes());
        $this->assertEquals(1, $votable->getDownvotes());
    }

    public function testVoteCollectionHasCorrectProperties(): void {
        $user = new User('u', 'p');

        $this->votable->vote(VotableInterface::VOTE_UP, $user, null);
        $this->assertEquals(VotableInterface::VOTE_UP, $this->votable->getVotes()->first()->getChoice());
        $this->assertCount(1, $this->votable->getVotes());

        $this->votable->vote(VotableInterface::VOTE_DOWN, $user, null);
        $this->assertEquals(VotableInterface::VOTE_DOWN, $this->votable->getVotes()->first()->getChoice());
        $this->assertCount(1, $this->votable->getVotes());

        $this->votable->vote(VotableInterface::VOTE_NONE, $user, null);
        $this->assertCount(0, $this->votable->getVotes());
    }

    /**
     * @expectedException \App\Entity\Exception\BadVoteChoiceException
     */
    public function testCannotGiveIncorrectVote(): void {
        $user = new User('u', 'p');

        $this->votable->vote(69, $user, null);
    }

    public function testGetUserVote(): void {
        $user1 = new User('u', 'p');
        $this->votable->vote(VotableInterface::VOTE_UP, $user1, null);

        $user2 = new User('u', 'p');
        $this->votable->vote(VotableInterface::VOTE_DOWN, $user2, null);

        $user3 = new User('u', 'p');

        $this->assertEquals(VotableInterface::VOTE_UP, $this->votable->getUserChoice($user1));
        $this->assertEquals(VotableInterface::VOTE_DOWN, $this->votable->getUserChoice($user2));
        $this->assertEquals(VotableInterface::VOTE_NONE, $this->votable->getUserChoice($user3));
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testAcceptsWellFormedIpAddresses(): void {
        $user = new User('u', 'p');
        $this->votable->vote(VotableInterface::VOTE_UP, $user, '127.0.4.20');
        $this->votable->vote(VotableInterface::VOTE_UP, $user, '::69');
        $this->votable->vote(VotableInterface::VOTE_UP, $user, null);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Bad IP address
     */
    public function testThrowsExceptionOnBadIpAddress(): void {
        $user = new User('u', 'p');

        $this->votable->vote(VotableInterface::VOTE_UP, $user, 'poop');
    }

    private function createVotable(): VotableInterface {
        return new class() implements VotableInterface {
            use VotableTrait;

            private $votes;

            public function __construct() {
                $this->votes = new ArrayCollection();
            }

            public function getVotes(): Collection {
                return $this->votes;
            }

            protected function createVote(int $choice, User $user, ?string $ip): Vote {
                return new class($choice, $user, $ip) extends Vote {
                };
            }
        };
    }
}
