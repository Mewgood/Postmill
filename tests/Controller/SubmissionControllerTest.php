<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCase;

/**
 * @coversDefaultClass \App\Controller\SubmissionController
 */
class SubmissionControllerTest extends WebTestCase {
    public function testCanCreateSubmission(): void {
        $client = self::createUserClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/submit');

        $form = $crawler->selectButton('Create submission')->form([
            'submission[title]' => 'Making a submission',
            'submission[url]' => 'http://www.foo.example/',
            'submission[body]' => "This is a test submission\n\na new line",
            'submission[forum]' => '2',
        ]);

        $crawler = $client->submit($form);

        $this->assertEquals('Making a submission', $crawler->filter('.submission__link')->text());
        $this->assertEquals('http://www.foo.example/', $crawler->filter('.submission__link')->attr('href'));
        $this->assertEquals("<p>This is a test submission</p>\n<p>a new line</p>\n", $crawler->filter('.submission__body')->html());
    }

    public function testSubmissionJson(): void {
        $client = self::createClient();
        $client->request('GET', '/f/news/1.json');

        $this->assertArraySubset([
            'url' => 'http://www.example.com/some/thing',
            'title' => 'A submission with a URL and body',
            'body' => 'This is a body.',
            'timestamp' => '2017-03-03T03:03:00+00:00',
            'renderedBody' => "<p>This is a body.</p>\n",
        ], json_decode($client->getResponse()->getContent(), true));
    }

    public function testSubmissionShortcut(): void {
        $client = self::createClient();
        $client->request('GET', '/1');

        $this->assertTrue($client->getResponse()->isRedirect('/f/news/1/a-submission-with-a-url-and-body'));
    }

    public function testEditingOwnSubmission(): void {
        $client = self::createUserClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/f/cats/3');
        $crawler = $client->click($crawler->selectLink('Edit')->link());
        $crawler = $client->submit($crawler->selectButton('Edit submission')->form([
            'submission[url]' => 'http://edited.url.example/',
            'submission[title]' => 'Edited submission title',
            'submission[body]' => 'Edited body',
        ]));

        $this->assertEquals('http://edited.url.example/', $crawler->filter('.submission__link')->attr('href'));
        $this->assertContains('Edited submission title', $crawler->filter('.submission__link')->text());
        $this->assertContains('Edited body', $crawler->filter('.submission__body')->text());
    }

    public function testDeletingOwnSubmissionWithCommentsResultsInSoftDeletion(): void {
        $client = self::createUserClient();

        $crawler = $client->request('GET', '/f/cats/3');
        $client->submit($crawler->selectButton('Delete')->form());

        $client->request('GET', '/f/cats/3');

        self::assertSelectorTextContains('.submission__link', '[deleted]');
    }

    public function testDeletingOwnSubmissionWithoutCommentsResultsInHardDeletion(): void {
        $client = self::createUserClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/f/cats/3');
        $crawler = $client->submit($crawler->filter('.comment')->selectButton('Delete')->form());
        $client->submit($crawler->selectButton('Delete')->form());

        $client->request('GET', '/f/cats/3');
        $this->assertTrue($client->getResponse()->isNotFound());
    }

    public function testSoftDeletingSubmissionOfOtherUser(): void {
        $client = self::createAdminClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/f/cats/3');
        $crawler = $client->click($crawler->selectLink('Delete')->link());
        $crawler = $client->submit($crawler->selectButton('Delete')->form([
            'delete_reason[reason]' => 'some reason',
        ]));

        $this->assertContains('The submission was deleted.', $crawler->filter('.alert__text')->text());

        $crawler = $client->request('GET', '/f/cats/3');
        $this->assertContains('[deleted]', $crawler->filter('.submission__link')->text());
    }

    public function testSubmissionLocking(): void {
        $client = self::createAdminClient();
        $client->followRedirects();
        $crawler = $client->request('GET', '/f/news/1');

        $crawler = $client->submit($crawler->selectButton('Lock')->form());
        $this->assertCount(1, $crawler->filter('.submission--locked'));
        $this->assertCount(1, $crawler->filter('.submission__locked-icon'));
        $this->assertContains('The submission was locked.', $crawler->filter('.alert__text')->text());

        $crawler = $client->submit($crawler->selectButton('Unlock')->form());
        $this->assertCount(0, $crawler->filter('.submission--locked'));
        $this->assertCount(0, $crawler->filter('.submission__locked-icon'));
        $this->assertContains('The submission was unlocked.', $crawler->filter('.alert__text')->text());
    }

    public function testSubmissionPinning(): void {
        $client = self::createAdminClient();
        $client->followRedirects();
        $crawler = $client->request('GET', '/f/news/1');

        $crawler = $client->submit($crawler->selectButton('Pin')->form());
        $this->assertCount(1, $crawler->filter('.submission--sticky'));
        $this->assertCount(1, $crawler->filter('.submission__sticky-icon'));
        $this->assertContains('The submission was pinned.', $crawler->filter('.alert__text')->text());

        $crawler = $client->submit($crawler->selectButton('Unpin')->form());
        $this->assertCount(0, $crawler->filter('.submission--sticky'));
        $this->assertCount(0, $crawler->filter('.submission__sticky-icon'));
        $this->assertContains('The submission was unpinned.', $crawler->filter('.alert__text')->text());
    }

    /**
     * @dataProvider selfDeleteReferrerProvider
     */
    public function testRedirectsProperlyAfterDelete(string $expected, string $referrer): void {
        $client = self::createUserClient();
        $crawler = $client->request('GET', $referrer);

        $client->submit($crawler->selectButton('Delete')->form());

        self::assertResponseRedirects($expected, null, "expected: $expected, referrer: $referrer");
    }

    /**
     * @covers \App\Controller\UserController::notifications
     */
    public function testCanReceiveSubmissionNotifications(): void {
        $client = self::createAdminClient();
        $crawler = $client->request('GET', '/f/cats/3');

        $form = $crawler->selectButton('reply_to_submission_3[submit]')->form([
            'reply_to_submission_3[comment]' => 'You will be notified about this comment.',
        ]);

        $client->submit($form);

        $client = self::createUserClient();
        $client->request('GET', '/notifications');

        self::assertSelectorTextContains('.comment__body', 'You will be notified about this comment.');
    }

    public function selfDeleteReferrerProvider(): iterable {
        yield ['http://localhost/', '/'];
        yield ['http://localhost/f/cats', '/f/cats'];
        yield ['/f/cats', '/f/cats/3'];
        yield ['/f/cats', '/f/cats/3/with-slug'];
    }
}
