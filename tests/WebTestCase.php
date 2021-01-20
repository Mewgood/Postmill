<?php

namespace App\Tests;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;

abstract class WebTestCase extends BaseWebTestCase {
    public static function createAdminClient(): KernelBrowser {
        return self::createClientWithAuthenticatedUser('emma');
    }

    public static function createUserClient(): KernelBrowser {
        return self::createClientWithAuthenticatedUser('zach');
    }

    public static function createClientWithAuthenticatedUser(string $username): KernelBrowser {
        $client = self::createClient([], [
            'HTTP_X_EXPERIMENTAL_API' => 1,
        ]);

        $user = self::$container
            ->get(UserRepository::class)
            ->loadUserByUsername($username);

        $client->loginUser($user);

        return $client;
    }
}
