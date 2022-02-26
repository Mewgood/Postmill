<?php

declare(strict_types=1);

namespace App\Tests\Security\Voter;

use App\Entity\Site;
use App\Entity\User;
use App\Repository\SiteRepository;
use App\Security\Voter\WikiVoter;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @covers \App\Security\Voter\WikiVoter
 */
final class WikiVoterTest extends VoterTestCase {
    /** @var SiteRepository&MockObject */
    private $siteRepository;

    protected function setUp(): void {
        $this->siteRepository = $this->createMock(SiteRepository::class);

        parent::setUp();
    }

    protected function getVoter(): VoterInterface {
        return new WikiVoter($this->decisionManager, $this->siteRepository);
    }

    /**
     * @psalm-param VoterInterface::ACCESS_* $expectedDecision
     * @dataProvider provideCreateArgs
     */
    public function testCreate(
        int $expectedDecision,
        string $editRole,
        TokenInterface $token
    ): void {
        $site = new Site();
        $site->setWikiEditRole('ROLE_USER');

        $this->siteRepository
            ->method('findCurrentSite')
            ->willReturn($site);

        $this->decisionManager
            ->method('decide')
            ->with($token, $token->getRoleNames())
            ->willReturn($expectedDecision === VoterInterface::ACCESS_GRANTED);

        $this->assertDecision(
            $expectedDecision,
            WikiVoter::ATTR_CREATE,
            null,
            $token,
        );
    }

    public function provideCreateArgs(): iterable {
        $user = $this->createMock(User::class);

        yield 'user with sufficient accesss is allowed' => [
            VoterInterface::ACCESS_GRANTED,
            'ROLE_USER',
            $this->createToken(['ROLE_USER'], $user),
        ];

        yield 'user with insufficient role is denied' => [
            VoterInterface::ACCESS_DENIED,
            'ROLE_WHITELISTED',
            $this->createToken(['ROLE_USER'], $user),
        ];

        yield 'anonymous user is denied' => [
            VoterInterface::ACCESS_DENIED,
            'ROLE_USER',
            $this->createToken([]),
        ];
    }
}
