<?php

namespace App\Tests\Message\Handler;

use App\Entity\Site;
use App\Message\Handler\DownloadSubmissionImageHandler;
use App\Message\NewSubmission;
use App\Repository\ImageRepository;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @covers \App\Message\Handler\DownloadSubmissionImageHandler
 */
class DownloadSubmissionImageHandlerTest extends TestCase {
    public function testDoesNotDownloadIfDisabledInSiteSettings(): void {
        $site = new Site();
        $site->setUrlImagesEnabled(false);

        $siteRepository = $this->createMock(SiteRepository::class);
        $siteRepository
            ->expects($this->once())
            ->method('findCurrentSite')
            ->willReturn($site);

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('Image downloading disabled in site settings');

        $handler = new DownloadSubmissionImageHandler(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(HttpClientInterface::class),
            $this->createMock(ImageRepository::class),
            $logger,
            $siteRepository,
            $this->createMock(ValidatorInterface::class)
        );

        $handler(new NewSubmission(1));
    }
}
