<?php

namespace App\Tests\Fixtures;

use App\Entity\WikiPage;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LoadExampleWiki extends AbstractFixture implements DependentFixtureInterface {
    public function load(ObjectManager $manager): void {
        /** @noinspection PhpParamsInspection */
        $page = new WikiPage(
            'index',
            'This is the title',
            'and this is the body',
            $this->getReference('user-emma'),
            new \DateTime('2019-04-20')
        );

        $manager->persist($page);
        $manager->flush();
    }

    public function getDependencies(): array {
        return [LoadExampleUsers::class];
    }
}
