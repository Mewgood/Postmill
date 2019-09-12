<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCase;

/**
 * @coversDefaultClass  \App\Controller\CommentController
 */
class CommentControllerTest extends WebTestCase {
    public function testCommentListing(): void {
        $client = self::createClient();
        $crawler = $client->request('GET', '/comments');

        $this->assertEquals(
            "<p>YET ANOTHER BORING COMMENT.</p>\n",
            $crawler->filter('.comment__body')->eq(0)->html()
        );

        $this->assertEquals(
            "<p>This is a reply to the previous comment.</p>\n",
            $crawler->filter('.comment__body')->eq(1)->html()
        );

        $this->assertEquals(
            "<p>This is a comment body. It is quite neat.</p>\n<p><em>markdown</em></p>\n",
            $crawler->filter('.comment__body')->eq(2)->html()
        );
    }

    public function testCanPostCommentInReplyToSubmission(): void {
        $client = self::createUserClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/f/cats/3');

        $crawler = $client->submit($crawler->selectButton('Post')->form([
            'reply_to_submission_3[comment]' => 'i think that is a neat idea!',
        ]));

        $this->assertEquals("<p>i think that is a neat idea!</p>\n", $crawler->filter('.comment__body')->html());
        $this->assertCount(0, $crawler->selectLink('Parent'));
    }

    public function testCanPostCommentInReplyToComment(): void {
        $client = self::createUserClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/f/cats/3/-/comment/3');

        $crawler = $client->submit($crawler->selectButton('Post')->form([
            'reply_to_comment_3[comment]' => 'squirrel',
        ]));

        $this->assertEquals("<p>squirrel</p>\n", $crawler->filter('.comment__body')->html());
        $this->assertCount(1, $crawler->selectLink('Parent'));
    }

    public function testBadCommentSubmitRedirectsToErrorForm(): void {
        $client = self::createUserClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/f/cats/3/-/comment/3');
        $crawler = $client->submit($crawler->selectButton('Post')->form([
            'reply_to_comment_3[comment]' => ' ',
        ]));

        $this->assertTrue($client->getRequest()->isMethod('POST'));
        $this->assertEquals('The comment must not be empty.', $crawler->filter('.form-error-list li')->text());
    }

    public function testCommentJson(): void {
        $client = self::createClient();
        $client->request('GET', '/f/cats/3/-/comment/3.json');

        $this->assertArraySubset([
            'id' => 3,
            'body' => 'YET ANOTHER BORING COMMENT.',
            'timestamp' => '2017-05-03T01:00:00+00:00',
            'user' => [
                'id' => 2,
                'username' => 'zach',
            ],
            'submission' => [
                'id' => 3,
                'forum' => [
                    'id' => 1,
                    'name' => 'cats',
                ],
            ],
            'visibility' => 'visible',
            'editedAt' => null,
            'userFlag' => 'none',
            'netScore' => 1,
            'upvotes' => 1,
            'downvotes' => 0,
            'parentId' => null,
            'replyCount' => 0,
            'renderedBody' => "<p>YET ANOTHER BORING COMMENT.</p>\n",
        ], json_decode($client->getResponse()->getContent(), true));
    }

    public function testCanEditOwnComment(): void {
        $client = self::createUserClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/f/cats/3');
        $crawler = $client->click($crawler->filter('.comment')->selectLink('Edit')->link());
        $crawler = $client->submit($crawler->selectButton('Save')->form([
            'comment[comment]' => 'edited comment',
        ]));

        $this->assertEquals("<p>edited comment</p>\n", $crawler->filter('.comment__body')->html());
    }

    public function testCanHardDeleteOwnCommentWithoutReply(): void {
        $client = self::createUserClient();

        $crawler = $client->request('GET', '/f/cats/3/-/comment/3');
        $client->submit($crawler->filter('.comment')->selectButton('Delete')->form());

        $client->request('GET', '/f/cats/3/-/comment/3');
        $this->assertTrue($client->getResponse()->isNotFound());
    }

    public function testCanSoftDeleteOwnCommentWithReply(): void {
        $client = self::createAdminClient();

        $crawler = $client->request('GET', '/f/news/1/-/comment/1');
        $client->submit($crawler->filter('.comment')->selectButton('Delete')->form());

        $crawler = $client->request('GET', '/f/news/1/-/comment/1');
        $this->assertCount(1, $crawler->filter('.comment--soft-deleted'));
    }

    /**
     * @dataProvider selfDeleteReferrerProvider
     */
    public function testRedirectsProperlyAfterDelete(string $expected, string $referrer): void {
        $client = self::createUserClient();
        $crawler = $client->request('GET', $referrer);

        $client->submit($crawler->filter('.comment')->selectButton('Delete')->form());

        self::assertResponseRedirects($expected, null, "expected: $expected, referrer: $referrer");
    }

    /**
     * @covers \App\Controller\UserController::notifications
     */
    public function testCanReceiveCommentNotifications(): void {
        $client = self::createAdminClient();
        $crawler = $client->request('GET', '/f/cats/3/-/comment/3');

        $form = $crawler->selectButton('reply_to_comment_3[submit]')->form([
            'reply_to_comment_3[comment]' => 'You will be notified about this comment.',
        ]);

        $client->submit($form);

        $client = self::createUserClient();
        $client->request('GET', '/notifications');

        self::assertSelectorTextContains('.comment__body', 'You will be notified about this comment.');
    }

    public function selfDeleteReferrerProvider(): iterable {
        yield ['http://localhost/f/cats/3', '/f/cats/3'];
        yield ['http://localhost/f/cats/3/with-slug', '/f/cats/3/with-slug'];
        yield ['/f/cats/3/submission-with-a-body', '/f/cats/3/-/comment/3'];
        yield ['/f/cats/3/submission-with-a-body', '/f/cats/3/with-slug/comment/3'];
    }
}
