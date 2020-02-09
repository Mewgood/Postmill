<?php

namespace App\Tests\Controller\Api;

use App\Tests\WebTestCase;

/**
 * @covers \App\Controller\Api\CommentController
 */
class CommentControllerTest extends WebTestCase {
    public function testCanListComments(): void {
        $client = self::createUserClient();
        $client->request('GET', '/api/comments');

        self::assertResponseStatusCodeSame(200);

        $this->assertSame([3, 2, 1], array_column(
            json_decode($client->getResponse()->getContent(), true)['entries'],
            'id'
        ));
    }

    public function testCanReadComment(): void {
        $client = self::createUserClient();
        $client->request('GET', '/api/comments/3');

        self::assertResponseStatusCodeSame(200);

        $this->assertEquals([
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
                'user' => [
                    'id' => 2,
                    'username' => 'zach',
                ],
                'slug' => 'submission-with-a-body',
            ],
            'parentId' => null,
            'replyCount' => 0,
            'visibility' => 'visible',
            'editedAt' => null,
            'moderated' => false,
            'userFlag' => 'none',
            'netScore' => 1,
            'upvotes' => 1,
            'downvotes' => 0,
            'renderedBody' => "<p>YET ANOTHER BORING COMMENT.</p>\n",
        ], json_decode($client->getResponse()->getContent(), true));
    }

    public function testCanUpdateComment(): void {
        $client = self::createUserClient();
        $client->request('PUT', '/api/comments/3', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'body' => 'this is the new comment body',
        ]));

        self::assertResponseStatusCodeSame(204);

        $client->request('GET', '/api/comments/3');

        $this->assertSame(
            'this is the new comment body',
            json_decode($client->getResponse()->getContent(), true)['body']
        );
    }

    public function testCannotUpdateCommentOfOtherUser(): void {
        $client = self::createUserClient();
        $client->request('PUT', '/api/comments/1', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'body' => 'shouldn\'t be stored',
        ]));

        self::assertResponseStatusCodeSame(403);
    }
}
