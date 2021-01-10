<?php

namespace App\Tests\Twig;

use App\Entity\Constants\SubmissionLinkDestination;
use App\Entity\Site;
use App\Repository\SiteRepository;
use App\Tests\Fixtures\Factory\EntityFactory;
use App\Twig\SettingsExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Security;

/**
 * @covers \App\Twig\SettingsExtension
 */
class SettingsExtensionTest extends TestCase {
    /**
     * @var Security|\PHPUnit\Framework\MockObject\MockObject
     */
    private $security;

    /**
     * @var SiteRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $sites;

    /**
     * @var SettingsExtension
     */
    private $extension;

    protected function setUp(): void {
        $this->security = $this->createMock(Security::class);
        $this->sites = $this->createMock(SiteRepository::class);

        $this->extension = new SettingsExtension(
            $this->security,
            $this->sites,
        );
    }

    /**
     * @dataProvider provideSubmissionLinkDestinations
     */
    public function testGetSubmissionLinkDestinationFromUserSettings(?string $destination): void {
        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturnCallback(function () use ($destination) {
                $user = EntityFactory::makeUser();
                $user->setSubmissionLinkDestination($destination);

                return $user;
            });

        $this->sites
            ->expects($this->never())
            ->method('findCurrentSite');

        $this->assertSame($destination, $this->extension->getSubmissionLinkDestination());
    }

    /**
     * @dataProvider provideSubmissionLinkDestinations
     */
    public function testGetSubmissionLinkDestinationFromSiteSettings(string $destination): void {
        $this->security
            ->expects($this->once())
            ->method('getUser');

        $this->sites
            ->expects($this->once())
            ->method('findCurrentSite')
            ->willReturnCallback(function () use ($destination) {
                $site = new Site();
                $site->setSubmissionLinkDestination($destination);

                return $site;
            });

        $this->assertSame($destination, $this->extension->getSubmissionLinkDestination());
    }

    public function provideSubmissionLinkDestinations(): \Generator {
        foreach (SubmissionLinkDestination::OPTIONS as $destination) {
            yield [$destination];
        }
    }
}
