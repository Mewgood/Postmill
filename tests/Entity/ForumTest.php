<?php

namespace App\Tests\Entity;

use App\Entity\Forum;
use App\Entity\ForumBan;
use App\Entity\Moderator;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @covers \App\Entity\Forum
 */
class ForumTest extends TestCase {
    /**
     * @var Forum
     */
    private $forum;

    protected function setUp(): void {
        $this->forum = EntityFactory::makeForum();
    }

    /**
     * @dataProvider nonPrivilegedProvider
     * @param mixed $nonPrivilegedUser
     */
    public function testRandomsAreNotModerators($nonPrivilegedUser): void {
        $this->assertFalse($this->forum->userIsModerator($nonPrivilegedUser));
    }

    public function testModeratorsAreModerators(): void {
        $user = EntityFactory::makeUser();
        new Moderator($this->forum, $user);

        $admin = EntityFactory::makeUser();
        $admin->setAdmin(true);
        new Moderator($this->forum, $admin);

        $this->assertTrue($this->forum->userIsModerator($user));
        $this->assertTrue($this->forum->userIsModerator($admin));
    }

    public function testAdminsAreNotModeratorsWithFlag(): void {
        $user = EntityFactory::makeUser();
        $user->setAdmin(true);

        $this->assertFalse($this->forum->userIsModerator($user, false));
    }

    /**
     * @dataProvider nonPrivilegedProvider
     * @param $nonPrivilegedUser mixed
     */
    public function testRandomsCanNotDeleteForum($nonPrivilegedUser): void {
        $this->assertFalse($this->forum->userCanDelete($nonPrivilegedUser));
    }

    public function testAdminCanDeleteEmptyForum(): void {
        $user = EntityFactory::makeUser();
        $user->setAdmin(true);

        $this->assertTrue($this->forum->userCanDelete($user));
    }

    public function testModeratorCanDeleteEmptyForum(): void {
        $user = EntityFactory::makeUser();
        new Moderator($this->forum, $user);

        $this->assertTrue($this->forum->userCanDelete($user));
    }

    public function testUserIsNotBannedInNewForum(): void {
        $this->assertFalse($this->forum->userIsBanned(EntityFactory::makeUser()));
    }

    public function testBansWithoutExpiryTimesWork(): void {
        $user = EntityFactory::makeUser();

        $this->forum->addBan(new ForumBan($this->forum, $user, 'a', true, EntityFactory::makeUser()));

        $this->assertTrue($this->forum->userIsBanned($user));
    }

    public function testBansWithExpiryTimesWork(): void {
        $user = EntityFactory::makeUser();

        $this->forum->addBan(new ForumBan($this->forum, $user, 'a', true, EntityFactory::makeUser(), new \DateTime('+2 weeks')));

        $this->assertTrue($this->forum->userIsBanned($user));
    }

    public function testBansCanExpire(): void {
        $user = EntityFactory::makeUser();

        $this->forum->addBan(new ForumBan($this->forum, $user, 'a', true, EntityFactory::makeUser(), new \DateTime('-2 weeks')));

        $this->assertFalse($this->forum->userIsBanned($user));
    }

    public function testAdminUserIsNeverBanned(): void {
        $user = EntityFactory::makeUser();
        $user->setAdmin(true);

        $this->forum->addBan(new ForumBan($this->forum, $user, 'a', true, EntityFactory::makeUser()));

        $this->assertFalse($this->forum->userIsBanned($user));
    }

    public function testUnbansWork(): void {
        $user = EntityFactory::makeUser();

        $this->forum->addBan(new ForumBan($this->forum, $user, 'ben', true, EntityFactory::makeUser()));
        $this->forum->addBan(new ForumBan($this->forum, $user, 'unben', false, EntityFactory::makeUser()));

        $this->assertFalse($this->forum->userIsBanned($user));
    }

    public function nonPrivilegedProvider(): iterable {
        yield [null];
        yield [$this->createMock(UserInterface::class)];
        yield ['anon.'];
        yield [EntityFactory::makeUser()];
    }
}
