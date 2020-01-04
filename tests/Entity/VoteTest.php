<?php

namespace App\Tests\Entity;

use App\Entity\Contracts\VotableInterface;
use App\Entity\User;
use App\Entity\Vote;
use PHPUnit\Framework\TestCase;

class VoteTest extends TestCase {
    /**
     * @dataProvider provideValidChoices
     */
    public function testAcceptsValidChoice(int $choice): void {
        /** @var Vote $vote */
        $vote = $this->getMockBuilder(Vote::class)
            ->setConstructorArgs([
                $choice,
                $this->createMock(User::class),
                null
            ])
            ->getMockForAbstractClass();

        $this->assertSame($choice, $vote->getChoice());
        $this->assertSame($choice === VotableInterface::VOTE_UP, $vote->getUpvote());
    }

    /**
     * @dataProvider provideInvalidChoices
     */
    public function testDoesNotAcceptInvalidChoice(string $expectedMessage, int $vote): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->getMockBuilder(Vote::class)
            ->setConstructorArgs([
                $vote,
                $this->createMock(User::class),
                null,
            ])
            ->getMockForAbstractClass();
    }

    public function testDoesNotAcceptBadIpAddress(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Bad IP address');

        $this->getMockBuilder(Vote::class)
            ->setConstructorArgs([
                VotableInterface::VOTE_UP,
                $this->createMock(User::class),
                'poo',
            ])
            ->getMockForAbstractClass();
    }

    /**
     * @dataProvider provideExpectedIpWhitelistMap
     */
    public function testConstructorSavesIpDependsOnUserWhitelistStatus(?string $expectedIp, bool $whitelisted): void {
        $user = $this->createMock(User::class);
        $user
            ->expects($this->once())
            ->method('isWhitelistedOrAdmin')
            ->willReturn($whitelisted);

        /** @var Vote $vote */
        $vote = $this->getMockBuilder(Vote::class)
            ->setConstructorArgs([
                VotableInterface::VOTE_UP,
                $user,
                '127.0.0.1',
            ])
            ->getMockForAbstractClass();

        $this->assertSame($expectedIp, $vote->getIp());
    }

    public function provideValidChoices(): iterable {
        yield [VotableInterface::VOTE_UP];
        yield [VotableInterface::VOTE_DOWN];
    }

    public function provideInvalidChoices(): iterable {
        yield ['A vote entity cannot have a "none" status', VotableInterface::VOTE_NONE];
        yield ['Unknown choice', 2];
        yield ['Unknown choice', -412];
    }

    public function provideExpectedIpWhitelistMap(): iterable {
        yield [null, true];
        yield ['127.0.0.1', false];
    }
}
