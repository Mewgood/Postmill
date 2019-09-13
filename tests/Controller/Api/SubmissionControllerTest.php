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

        $this->assertArraySubset([
            'entries' => [
                ['id' => 3],
                ['id' => 2],
                ['id' => 1],
            ]
        ], json_decode($client->getResponse()->getContent(), true));
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

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsInt($response['id']);
        $this->assertArraySubset([
            'title' => 'A submission posted via the API',
            'body' => 'very cool',
            'renderedBody' => "<p>very cool</p>\n",
            'forum' => [
                'id' => 2,
                'name' => 'news',
            ],
        ], $response);
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

        $this->assertArraySubset([
            'url' => 'http://www.example.com/',
            'title' => 'updated title',
            'body' => 'updated body',
        ], json_decode($client->getResponse()->getContent(), true));
    }

    public function testSoftDeleteOwnSubmission(): void {
        $client = self::createUserClient();
        $client->request('DELETE', '/api/submissions/3');

        self::assertResponseStatusCodeSame(204);

        $client->request('GET', '/api/submissions/3');

        self::assertResponseStatusCodeSame(200);
        $this->assertArraySubset([
            'id' => 3,
            'title' => '',
            'body' => '',
            'visibility' => 'deleted',
        ], json_decode($client->getResponse()->getContent(), true));
    }

    public function testCannotDeleteSubmissionOfOtherUser(): void {
        $client = self::createUserClient();
        $client->request('DELETE', '/api/submissions/2');

        self::assertResponseStatusCodeSame(403);
    }
}
