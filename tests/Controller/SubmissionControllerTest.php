<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCase;

/**
 * @covers \App\Controller\SubmissionController
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
        $client->followRedirects();

        $crawler = $client->request('GET', '/f/cats/3');
        $crawler = $client->submit($crawler->selectButton('Delete')->form());

        $this->assertContains('[deleted]', $crawler->filter('.submission__link')->text());
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
}
