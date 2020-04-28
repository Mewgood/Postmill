<?php

namespace App\Tests\Controller\Api;

use App\Tests\WebTestCase;

/**
 * @covers \App\Controller\Api\SubmissionController
 */
class SubmissionControllerTest extends WebTestCase {
    public function testListSubmissions(): void {
        $client = self::createUserClient();
        $client->request('GET', '/api/submissions');

        self::assertResponseStatusCodeSame(200);

        $this->assertSame([3, 2, 1], array_column(
            json_decode($client->getResponse()->getContent(), true)['entries'],
            'id'
        ));
    }

    public function testCannotListSubmissionsWithInvalidSortMode(): void {
        $client = self::createUserClient();
        $client->request('GET', '/api/submissions?sortBy=poo');

        self::assertResponseStatusCodeSame(400);
    }

    public function testGetSubmission(): void {
        $client = self::createUserClient();
        $client->request('GET', '/api/submissions/3');

        self::assertResponseStatusCodeSame(200);

        $this->assertEquals([
            'id' => 3,
            'title' => 'Submission with a body',
            'url' => null,
            'body' => "I'm bad at making stuff up.",
            'mediaType' => 'url',
            'commentCount' => 1,
            'timestamp' => '2017-04-28T10:00:00+00:00',
            'lastActive' => '2017-05-03T01:00:00+00:00',
            'visibility' => 'visible',
            'forum' => [
                'id' => 1,
                'name' => 'cats',
            ],
            'user' => [
                'id' => 2,
                'username' => 'zach',
            ],
            'netScore' => 1,
            'upvotes' => 1,
            'downvotes' => 0,
            'image' => null,
            'sticky' => false,
            'editedAt' => null,
            'moderated' => false,
            'userFlag' => 'none',
            'locked' => false,
            'slug' => 'submission-with-a-body',
            'renderedBody' => "<p>I'm bad at making stuff up.</p>\n",
            'thumbnail_1x' => null,
            'thumbnail_2x' => null,
        ], json_decode($client->getResponse()->getContent(), true));
    }

    public function testPostSubmission(): void {
        $client = self::createUserClient();

        $client->request('POST', '/api/submissions', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'A submission posted via the API',
            'body' => 'very cool',
            'forum' => 2,
        ]));

        self::assertResponseStatusCodeSame(201);

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsInt($data['id']);
        $this->assertSame('A submission posted via the API', $data['title']);
        $this->assertSame('very cool', $data['body']);
        $this->assertSame("<p>very cool</p>\n", $data['renderedBody']);
        $this->assertSame(2, $data['forum']['id']);
        $this->assertSame('news', $data['forum']['name']);
    }

    public function testUpdateSubmission(): void {
        $client = self::createUserClient();

        $client->request('PUT', '/api/submissions/3', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'url' => 'http://www.example.com/',
            'title' => 'updated title',
            'body' => 'updated body',
        ]));

        self::assertResponseStatusCodeSame(204);

        $client->request('GET', '/api/submissions/3');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('http://www.example.com/', $data['url']);
        $this->assertSame('updated title', $data['title']);
        $this->assertSame('updated body', $data['body']);
    }

    public function testSoftDeleteOwnSubmission(): void {
        $client = self::createUserClient();
        $client->request('DELETE', '/api/submissions/3');

        self::assertResponseStatusCodeSame(204);

        $client->request('GET', '/api/submissions/3');

        self::assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(3, $data['id']);
        $this->assertSame('', $data['title']);
        $this->assertNull($data['body']);
        $this->assertSame('soft_deleted', $data['visibility']);
    }

    public function testCannotDeleteSubmissionOfOtherUser(): void {
        $client = self::createUserClient();
        $client->request('DELETE', '/api/submissions/2');

        self::assertResponseStatusCodeSame(403);
    }

    public function testCanReadSubmissionComments(): void {
        $client = self::createUserClient();
        $client->request('GET', '/api/submissions/1/comments');

        self::assertResponseStatusCodeSame(200);

        $this->assertEquals([
            [
                'id' => 1,
                'body' => "This is a comment body. It is quite neat.\n\n*markdown*",
                'timestamp' => '2017-05-01T12:00:00+00:00',
                'user' => [
                    'id' => 1,
                    'username' => 'emma',
                ],
                'submission' => [
                    'id' => 1,
                    'forum' => [
                        'id' => 2,
                        'name' => 'news',
                    ],
                    'user' => [
                        'id' => 1,
                        'username' => 'emma',
                    ],
                    'slug' => 'a-submission-with-a-url-and-body',
                ],
                'parentId' => null,
                'replies' => [
                    [
                        'id' => 2,
                        'body' => 'This is a reply to the previous comment.',
                        'timestamp' => '2017-05-02T14:00:00+00:00',
                        'user' => [
                            'id' => 2,
                            'username' => 'zach',
                        ],
                        'submission' => [
                            'id' => 1,
                            'forum' => [
                                'id' => 2,
                                'name' => 'news',
                            ],
                            'user' => [
                                'id' => 1,
                                'username' => 'emma',
                            ],
                            'slug' => 'a-submission-with-a-url-and-body',
                        ],
                        'parentId' => 1,
                        'replies' => [],
                        'replyCount' => 0,
                        'visibility' => 'visible',
                        'editedAt' => null,
                        'moderated' => false,
                        'userFlag' => 'none',
                        'netScore' => 1,
                        'upvotes' => 1,
                        'downvotes' => 0,
                        'renderedBody' => "<p>This is a reply to the previous comment.</p>\n",
                    ],
                ],
                'replyCount' => 1,
                'visibility' => 'visible',
                'editedAt' => null,
                'moderated' => false,
                'userFlag' => 'none',
                'netScore' => 1,
                'upvotes' => 1,
                'downvotes' => 0,
                'renderedBody' => "<p>This is a comment body. It is quite neat.</p>\n<p><em>markdown</em></p>\n",
            ],
        ], json_decode($client->getResponse()->getContent(), true));
    }
}
