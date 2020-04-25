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

        $this->assertSame('Making a submission', $crawler->filter('.submission__link')->text());
        $this->assertSame('http://www.foo.example/', $crawler->filter('.submission__link')->attr('href'));
        $this->assertSame("<p>This is a test submission</p>\n<p>a new line</p>\n", $crawler->filter('.submission__body')->html());
    }

    public function testCanCreateSubmissionWithImage(): void {
        $client = self::createAdminClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/submit');

        $form = $crawler->selectButton('Create submission')->form([
            'submission[title]' => 'Submission with image',
            'submission[mediaType]' => 'image',
            'submission[forum]' => '2',
        ]);
        $form['submission[image]']->upload(__DIR__.'/../Resources/120px-12-Color-SVG.svg.png');

        $crawler = $client->submit($form);

        $this->assertSame(
            'http://localhost/submission_images/a91d6c2201d32b8c39bff1143a5b29e74b740248c5d65810ddcbfa16228d49e9.png',
            $crawler->filter('.submission__link')->attr('href')
        );
    }

    public function testCannotCreateSubmissionWithInvalidImage(): void {
        $client = self::createAdminClient();
        $crawler = $client->request('GET', '/submit');

        $form = $crawler->selectButton('Create submission')->form([
            'submission[title]' => 'Non-submission with non-image',
            'submission[mediaType]' => 'image',
            'submission[forum]' => '2',
        ]);
        $form['submission[image]']->upload(__DIR__.'/../Resources/garbage.bin');

        $client->submit($form);

        self::assertResponseStatusCodeSame(200);
        self::assertSelectorTextContains('.form-error-list', 'This file is not a valid image.');
    }

    public function testSubmissionJson(): void {
        $client = self::createClient();
        $client->request('GET', '/f/news/1.json');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('http://www.example.com/some/thing', $data['url']);
        $this->assertSame('A submission with a URL and body', $data['title']);
        $this->assertSame('This is a body.', $data['body']);
        $this->assertSame('2017-03-03T03:03:00+00:00', $data['timestamp']);
        $this->assertSame("<p>This is a body.</p>\n", $data['renderedBody']);
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

        $this->assertSame('http://edited.url.example/', $crawler->filter('.submission__link')->attr('href'));
        self::assertSelectorTextContains('.submission__link', 'Edited submission title');
        self::assertSelectorTextContains('.submission__body', 'Edited body');
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
        $client->submit($crawler->selectButton('Delete')->form([
            'delete_reason[reason]' => 'some reason',
        ]));

        self::assertSelectorTextContains('.alert__text', 'The submission was deleted.');

        $client->request('GET', '/f/cats/3');
        self::assertSelectorTextContains('.submission__link', '[deleted]');
    }

    public function testSubmissionLocking(): void {
        $client = self::createAdminClient();
        $client->followRedirects();
        $crawler = $client->request('GET', '/f/news/1');

        $crawler = $client->submit($crawler->selectButton('Lock')->form());
        $this->assertCount(1, $crawler->filter('.submission--locked'));
        $this->assertCount(1, $crawler->filter('.submission__locked-icon'));
        self::assertSelectorTextContains('.alert__text', 'The submission was locked.');

        $crawler = $client->submit($crawler->selectButton('Unlock')->form());
        $this->assertCount(0, $crawler->filter('.submission--locked'));
        $this->assertCount(0, $crawler->filter('.submission__locked-icon'));
        self::assertSelectorTextContains('.alert__text', 'The submission was unlocked.');
    }

    public function testSubmissionPinning(): void {
        $client = self::createAdminClient();
        $client->followRedirects();
        $crawler = $client->request('GET', '/f/news/1');

        $crawler = $client->submit($crawler->selectButton('Pin')->form());
        $this->assertCount(1, $crawler->filter('.submission--sticky'));
        $this->assertCount(1, $crawler->filter('.submission__sticky-icon'));
        self::assertSelectorTextContains('.alert__text', 'The submission was pinned.');

        $crawler = $client->submit($crawler->selectButton('Unpin')->form());
        $this->assertCount(0, $crawler->filter('.submission--sticky'));
        $this->assertCount(0, $crawler->filter('.submission__sticky-icon'));
        self::assertSelectorTextContains('.alert__text', 'The submission was unpinned.');
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

        $form = $crawler->selectButton('Post')->form([
            'reply_to_submission_3[comment]' => 'You will be notified about this comment.',
        ]);

        $client->submit($form);
        self::ensureKernelShutdown();

        $client = self::createUserClient();
        $client->request('GET', '/notifications');

        self::assertSelectorTextContains('.comment__body', 'You will be notified about this comment.');
    }

    /**
     * @see https://gitlab.com/postmill/Postmill/-/issues/59
     *
     * @group time-sensitive
     */
    public function testNonWhitelistedUsersGetErrorWhenPostingRapidly(): void {
        $client = self::createUserClient();

        for ($i = 0; $i < 3; $i++) {
            $crawler = $client->request('GET', '/submit/cats');
            $client->submit($crawler->selectButton('Create submission')->form([
                'submission[title]' => 'post '.$i,
            ]));
            $client->followRedirect();
            self::assertSelectorTextContains('.submission__title', 'post '.$i);
        }

        $crawler = $client->request('GET', '/submit/cats');
        $client->submit($crawler->selectButton('Create submission')->form([
            'submission[title]' => 'will not be posted',
        ]));

        self::assertResponseStatusCodeSame(200);
        self::assertSelectorTextContains(
            '.form-error-list li',
            'You cannot post more. Wait a while before trying again.'
        );
    }

    public function selfDeleteReferrerProvider(): iterable {
        yield ['http://localhost/', '/'];
        yield ['http://localhost/f/cats', '/f/cats'];
        yield ['/f/cats', '/f/cats/3'];
        yield ['/f/cats', '/f/cats/3/with-slug'];
    }
}
