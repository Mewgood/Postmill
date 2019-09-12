<?php

namespace App\Tests\Controller\Api;

use App\Tests\WebTestCase;

class UserControllerTest extends WebTestCase {
    public function testGetUser(): void {
        $client = self::createUserClient();
        $client->request('GET', '/api/users/1');

        $this->assertEquals([
            'id' => 1,
            'username' => 'emma',
            'created' => '2017-01-01T12:12:12+00:00',
            'admin' => true,
            'biography' => null,
            'renderedBiography' => null,
        ], json_decode($client->getResponse()->getContent(), true));
    }

    public function testSelf(): void {
        $client = self::createUserClient();
        $client->request('GET', '/api/users/self');

        $this->assertEquals([
            'id' => 2,
            'username' => 'zach',
        ], json_decode($client->getResponse()->getContent(), true));
    }

    public function testReadPreferences(): void {
        $client = self::createUserClient();
        $client->request('GET', '/api/users/2/preferences');

        $this->assertEquals([
            'locale' => 'en',
            'frontPage' => 'subscribed',
            'frontPageSortMode' => 'hot',
            'showCustomStylesheets' => true,
            'preferredTheme' => null,
            'openExternalLinksInNewTab' => false,
            'autoFetchSubmissionTitles' => true,
            'enablePostPreviews' => true,
            'showThumbnails' => true,
            'allowPrivateMessages' => true,
            'notifyOnReply' => true,
            'notifyOnMentions' => true,
            'preferredFonts' => null,
            'timezone' => 'UTC',
        ], json_decode($client->getResponse()->getContent(), true));
    }

    public function testUpdatePreferences(): void {
        $client = self::createUserClient();
        $client->request('PUT', '/api/users/2/preferences', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'frontPage' => 'all',
            'frontPageSortMode' => 'active',
            'openExternalLinksInNewTab' => true,
            'preferredFonts' => 'DejaVu Sans Mono, monospace',
        ]));

        self::assertResponseStatusCodeSame(204);

        $client->request('GET', '/api/users/2/preferences');

        $this->assertArraySubset([
            'frontPage' => 'all',
            'frontPageSortMode' => 'active',
            'openExternalLinksInNewTab' => true,
            'preferredFonts' => 'DejaVu Sans Mono, monospace',
        ], json_decode($client->getResponse()->getContent(), true));
    }
}
