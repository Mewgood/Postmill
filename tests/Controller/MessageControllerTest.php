<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCase;

/**
 * @covers \App\Controller\MessageController
 */
class MessageControllerTest extends WebTestCase {
    /**
     * @dataProvider authProvider
     *
     * @param string $username
     * @param string $password
     */
    public function testCanViewMessageList($username, $password): void {
        $client = self::createClient([], [
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ]);

        $crawler = $client->request('GET', '/messages');

        $this->assertStringContainsString(
            'This is a message. There are many like it, but this one originates from a fixture.',
            $crawler->filter('tbody tr td:nth-child(1)')->text()
        );
        $this->assertSame('1', trim($crawler->filter('tbody tr td:nth-child(3)')->text()));
    }

    public function testMessageListIsEmptyForUserWithNoMessages(): void {
        $client = self::createClient([], [
            'PHP_AUTH_USER' => 'third',
            'PHP_AUTH_PW' => 'example3',
        ]);

        $client->request('GET', '/messages');

        self::assertSelectorTextContains('main p', 'There are no messages to display.');
    }

    public function testMustBeLoggedInToViewMessageList(): void {
        $client = self::createClient();
        $client->request('GET', '/messages');

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertStringEndsWith('/login', $client->getResponse()->headers->get('Location'));
    }

    /**
     * @dataProvider authProvider
     *
     * @param string $username
     * @param string $password
     */
    public function testCanReadOwnMessages($username, $password): void {
        $client = self::createClient([], [
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ]);

        $client->request('GET', '/messages/thread/1');

        self::assertSelectorTextContains(
            '.message__body p',
            'This is a message. There are many like it, but this one originates from a fixture.'
        );
    }

    public function testCannotReadOthersMessages(): void {
        $client = self::createClient([], [
            'PHP_AUTH_USER' => 'third',
            'PHP_AUTH_PW' => 'example3',
        ]);

        $client->request('GET', '/messages/thread/1');

        $this->assertTrue($client->getResponse()->isForbidden());
    }

    public function testCannotReadMessagesWhileLoggedOut(): void {
        $client = self::createClient();
        $client->request('GET', '/messages/thread/1');

        self::assertResponseRedirects('/login');
    }

    public function testCanReply(): void {
        $client = self::createAdminClient();
        $crawler = $client->request('GET', '/messages/thread/1');

        $form = $crawler->filter('form[name="message"] button')->form([
            'message[body]' => 'aaa',
        ]);

        $client->submit($form);
        $crawler = $client->followRedirect();

        $this->assertStringContainsString('aaa', $crawler->filter('.message__body')->eq(2)->text());
    }

    public function authProvider(): iterable {
        yield ['emma', 'goodshit'];
        yield ['zach', 'example2'];
    }
}
