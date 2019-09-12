<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;

abstract class WebTestCase extends BaseWebTestCase {
    public static function createAdminClient(): AbstractBrowser {
        return self::createClient([], [
            'PHP_AUTH_USER' => 'emma',
            'PHP_AUTH_PW' => 'goodshit',
            'HTTP_X_EXPERIMENTAL_API' => 1,
        ]);
    }

    public static function createUserClient(): AbstractBrowser {
        return self::createClient([], [
            'PHP_AUTH_USER' => 'zach',
            'PHP_AUTH_PW' => 'example2',
            'HTTP_X_EXPERIMENTAL_API' => 1,
        ]);
    }
}
