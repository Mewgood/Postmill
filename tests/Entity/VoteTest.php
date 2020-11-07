<?php

namespace App\Tests\Entity;

use App\Entity\Contracts\Votable;
use App\Entity\Exception\BadVoteChoiceException;
use App\Entity\User;
use App\Entity\Vote;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\Vote
 */
class VoteTest extends TestCase {
    /**
     * @dataProvider provideValidChoices
     */
    public function testAcceptsValidChoice(int $choice): void {
        /** @var Vote $vote */
        $vote = $this->getMockBuilder(Vote::class)
            ->setConstructorArgs([
                $choice,
                new User('u', 'p'),
                null,
            ])
            ->getMockForAbstractClass();

        $this->assertSame($choice, $vote->getChoice());
        $this->assertSame($choice === Votable::VOTE_UP, $vote->getUpvote());
    }

    /**
     * @dataProvider provideInvalidChoices
     */
    public function testDoesNotAcceptInvalidChoice(string $expectedMessage, int $vote): void {
        $this->expectException(BadVoteChoiceException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->getMockBuilder(Vote::class)
            ->setConstructorArgs([
                $vote,
                new User('u', 'p'),
                null,
            ])
            ->getMockForAbstractClass();
    }

    public function testDoesNotAcceptBadIpAddress(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Bad IP address');

        $this->getMockBuilder(Vote::class)
            ->setConstructorArgs([
                Votable::VOTE_UP,
                new User('u', 'p'),
                'poo',
            ])
            ->getMockForAbstractClass();
    }

    /**
     * @dataProvider provideExpectedIpWhitelistMap
     */
    public function testConstructorSavesIpDependsOnUserWhitelistStatus(?string $expectedIp, bool $whitelisted): void {
        $user = new User('u', 'p');
        $user->setWhitelisted($whitelisted);

        /** @var Vote $vote */
        $vote = $this->getMockBuilder(Vote::class)
            ->setConstructorArgs([
                Votable::VOTE_UP,
                $user,
                '127.0.0.1',
            ])
            ->getMockForAbstractClass();

        $this->assertSame($expectedIp, $vote->getIp());
    }

    public function provideValidChoices(): iterable {
        yield [Votable::VOTE_UP];
        yield [Votable::VOTE_DOWN];
    }

    public function provideInvalidChoices(): iterable {
        yield ['A vote entity cannot have a "none" status', Votable::VOTE_NONE];
        yield ['Unknown choice', 2];
        yield ['Unknown choice', -412];
    }

    public function provideExpectedIpWhitelistMap(): iterable {
        yield [null, true];
        yield ['127.0.0.1', false];
    }
}
