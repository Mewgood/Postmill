<?php

namespace App\Tests\Entity;

use App\Entity\Constants\SubmissionLinkDestination;
use App\Entity\Site;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\Site
 */
class SiteTest extends TestCase {
    /**
     * @var Site
     */
    private $site;

    protected function setUp(): void {
        $this->site = new Site();
    }

    /**
     * @dataProvider provideSubmissionLinkDestinations
     */
    public function testSetAndGetSubmissionLinkDestinations(string $destination): void {
        $this->assertSame(SubmissionLinkDestination::URL, $this->site->getSubmissionLinkDestination());
        $this->site->setSubmissionLinkDestination($destination);
        $this->assertSame($destination, $this->site->getSubmissionLinkDestination());
    }

    public function provideSubmissionLinkDestinations(): \Generator {
        foreach (SubmissionLinkDestination::OPTIONS as $destination) {
            yield [$destination];
        }
    }
}
