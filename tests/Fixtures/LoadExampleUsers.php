<?php

namespace App\Tests\Fixtures;

use App\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

class LoadExampleUsers extends AbstractFixture {
    public function load(ObjectManager $manager): void {
        foreach ($this->provideUsers() as $data) {
            // use plaintext passwords in fixtures to speed up tests
            $user = new User($data['username'], $data['password'], new \DateTime($data['created']));
            $user->setAdmin($data['admin']);
            $user->setEmail($data['email']);

            $this->addReference('user-'.$data['username'], $user);

            $manager->persist($user);
        }

        $manager->flush();
    }

    private function provideUsers(): iterable {
        yield [
            'username' => 'emma',
            'password' => 'goodshit',
            'created' => '2017-01-01T12:12:12+00:00',
            'email' => 'emma@example.com',
            'admin' => true,
        ];

        yield [
            'username' => 'zach',
            'password' => 'example2',
            'created' => '2017-01-02T12:12:12+00:00',
            'email' => 'zach@example.com',
            'admin' => false,
        ];

        yield [
            'username' => 'third',
            'password' => 'example3',
            'created' => '2017-01-03T12:12:12+00:00',
            'email' => 'third@example.net',
            'admin' => false,
        ];
    }
}
