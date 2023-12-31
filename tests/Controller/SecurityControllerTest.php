<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\EventListener\CookieCheckingListener;
use App\Tests\WebTestCase;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @covers \App\Controller\SecurityController
 */
class SecurityControllerTest extends WebTestCase {
    /**
     * @group time-sensitive
     * @covers \App\EventListener\CookieCheckingListener
     */
    public function testCookieCheckApplies(): void {
        ClockMock::register(CookieCheckingListener::class);

        $client = self::createClient();
        $client->request('GET', '/login');

        self::assertResponseRedirects('http://localhost/login?_cookie_check='.time());
    }

    /**
     * @covers \App\EventListener\CookieCheckingListener
     */
    public function testFailedCookieCheckShowsError(): void {
        $client = self::createClient();
        $client->request('GET', '/login');
        $client->getCookieJar()->clear();
        $client->followRedirect();

        self::assertResponseStatusCodeSame(403);
    }

    public function testLoginRedirectsToFrontAndIsRemembered(): void {
        $client = self::createClient();
        $client->request('GET', '/login');
        $client->followRedirect();
        $client->submitForm('Log in', [
            '_username' => 'emma',
            '_password' => 'goodshit',
        ]);

        $this->assertResponseRedirects('/');
        $this->assertLoggedIn();
        $this->assertBrowserHasCookie('REMEMBERME');
    }

    public function testCannotLogInWithInvalidCredentials(): void {
        $client = self::createClient();
        $client->request('GET', '/login');
        $client->followRedirect();
        $client->submitForm('Log in', [
            '_username' => 'emma',
            '_password' => 'badshit',
        ]);

        $this->assertResponseRedirects('/login');
        $this->assertNotLoggedIn();
    }

    public function testCannotLogInWithInvalidCsrfToken(): void {
        $client = self::createClient();
        $client->request('GET', '/login');
        $client->followRedirect();
        $client->submitForm('Log in', [
            '_username' => 'emma',
            '_password' => 'goodshit',
            '_csrf_token' => 'not valid',
        ]);

        $this->assertResponseRedirects('/login');
        $this->assertNotLoggedIn();
    }

    public function testLoginFailureRetainsUsernameAndRememberMeInForm(): void {
        $client = self::createClient();
        $client->request('GET', '/login');
        $client->followRedirect();
        $client->submitForm('Log in', [
            '_username' => 'emma',
            '_password' => 'badshit',
            '_remember_me' => false,
        ]);

        $this->assertResponseRedirects('/login');

        $crawler = $client->followRedirect();

        $this->assertEquals('emma', $crawler->filter('[name="_username"]')->attr('value'));
        $this->assertEquals('', $crawler->filter('[name="_password"]')->attr('value'));
        $this->assertNull($crawler->filter('[name="_remember_me"]')->attr('checked'));
    }

    public function testUserCanChooseNotToBeRemembered(): void {
        $client = self::createClient();
        $client->request('GET', '/login');
        $client->followRedirect();
        $client->submitForm('Log in', [
            '_username' => 'emma',
            '_password' => 'goodshit',
            '_remember_me' => false,
        ]);

        $this->assertResponseRedirects('/');

        $client->followRedirect();

        $this->assertLoggedIn();
        $this->assertBrowserNotHasCookie('REMEMBERME');
    }

    public function testLoginTriggeredByAccessingRestrictedAreaRedirectsBack(): void {
        $client = self::createClient();
        $client->request('GET', '/site/settings');

        self::assertResponseRedirects('/login');

        $client->followRedirect();
        $client->submitForm('Log in', [
            '_username' => 'emma',
            '_password' => 'goodshit',
        ]);

        self::assertResponseRedirects('http://localhost/site/settings');
    }

    private function assertLoggedIn(): void {
        $token = self::getContainer()->get(TokenStorageInterface::class)->getToken();
        $this->assertNotNull($token);
        $this->assertInstanceOf(User::class, $token->getUser());
    }

    private function assertNotLoggedIn(): void {
        $this->assertNull(
            self::getContainer()->get(TokenStorageInterface::class)
                ->getToken()
        );
    }
}
