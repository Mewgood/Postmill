<?php

namespace App\Tests\Fixtures\Database;

use App\Entity\Forum;
use App\Entity\Moderator;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LoadExampleForums extends AbstractFixture implements DependentFixtureInterface {
    public function load(ObjectManager $manager): void {
        foreach ($this->provideForums() as $data) {
            $forum = new Forum(
                $data['name'],
                $data['title'],
                $data['description'],
                $data['sidebar'],
                null,
                $data['created']
            );

            $forum->setFeatured($data['featured']);

            foreach ($data['moderators'] as $username) {
                /* @noinspection PhpParamsInspection */
                new Moderator($forum, $this->getReference('user-'.$username), $data['created']);
            }

            foreach ($data['subscribers'] as $username) {
                /* @noinspection PhpParamsInspection */
                $forum->subscribe($this->getReference('user-'.$username));
            }

            $this->addReference('forum-'.$data['name'], $forum);

            $manager->persist($forum);
        }

        $manager->flush();
    }

    private function provideForums(): iterable {
        yield [
            'name' => 'cats',
            'title' => 'Cat Memes',
            'sidebar' => 'le memes',
            'description' => 'memes',
            'moderators' => ['emma', 'zach'],
            'subscribers' => ['emma', 'zach', 'third'],
            'created' => new \DateTime('2017-04-20 13:12'),
            'featured' => true,
        ];

        yield [
            'name' => 'news',
            'title' => 'News',
            'sidebar' => "Discussion of current events\n\n### Rules\n\n* rulez go here",
            'description' => 'Discussion of current events',
            'moderators' => ['zach'],
            'subscribers' => ['zach'],
            'created' => new \DateTime('2017-01-01 00:00'),
            'featured' => false,
        ];
    }

    public function getDependencies(): array {
        return [LoadExampleUsers::class];
    }
}
