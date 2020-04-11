<?php

namespace App\Tests\Fixtures\Database;

use App\Entity\Forum;
use App\Entity\ForumCategory;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LoadExampleForumCategories extends AbstractFixture implements DependentFixtureInterface {
    public function load(ObjectManager $manager): void {
        $category = new ForumCategory('pets', 'Pets!', 'its about pets', 'fluffy pets and stuff');
        /** @var Forum $forum */
        $forum = $this->getReference('forum-cats');
        $forum->setCategory($category);

        $manager->persist($category);
        $manager->flush();
    }

    public function getDependencies(): array {
        return [LoadExampleForums::class];
    }
}
