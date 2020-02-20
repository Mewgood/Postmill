<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCase;

/**
 * @covers \App\Controller\AjaxController
 */
class AjaxControllerTest extends WebTestCase {
    public function testPopperLoggedOut(): void {
        $client = self::createClient();
        $client->request('GET', '/_up/emma');

        self::assertResponseIsSuccessful();
    }

    public function testPopperForOtherUserHasCorrectNumberOfButtons(): void {
        self::createUserClient()->request('GET', '/_up/emma');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[href="/user/emma"]');
        self::assertSelectorExists('[href="/user/emma/block_user"]');
        self::assertSelectorExists('[href="/user/emma/compose_message"]');
    }

    public function testPopperForSelfHasOnlyProfileButton(): void {
        self::createUserClient()->request('GET', '/_up/zach');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[href="/user/zach"]');
        self::assertSelectorNotExists('[href="/user/zach/block_user"]');
        self::assertSelectorNotExists('[href="/user/zach/compose_message"]');
    }

    public function testPopperForBlockedUserHasWorkingUnblockButton(): void {
        $client = self::createUserClient();
        $crawler = $client->request('GET', '/user/emma/block_user');
        $client->submit($crawler->selectButton('Block')->form());
        self::assertResponseRedirects('/user/zach/block_list');

        $crawler = $client->request('GET', '/_up/emma');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[action="/user/emma/unblock_user"]');
        self::assertSelectorNotExists('[href="/user/emma/block_user"]');

        $client->submit($crawler->selectButton('Unblock')->form());
        self::assertResponseRedirects('http://localhost/_up/emma');

        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[href="/user/emma/block_user"]');
        self::assertSelectorNotExists('[action="/user/emma/unblock_user"]');
    }
}
