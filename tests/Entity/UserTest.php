<?php

namespace App\Tests\Entity;

use App\Entity\Constants\SubmissionLinkDestination;
use App\Entity\User;
use App\Entity\UserBan;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\User
 */
class UserTest extends TestCase {
    /**
     * @dataProvider unnormalizedUserProvider
     *
     * @param string $expected
     * @param string $input
     */
    public function testCanNormalizeUsername($expected, $input): void {
        $this->assertSame($expected, User::normalizeUsername($input));
    }

    /**
     * @dataProvider unnormalizedEmailAddressProvider
     *
     * @param string $expected
     * @param string $input
     */
    public function testCanNormalizeEmail($expected, $input): void {
        $this->assertSame($expected, User::normalizeEmail($input));
    }

    public function testNewUserIsNotBanned(): void {
        $user = EntityFactory::makeUser();

        $this->assertFalse($user->isBanned());
    }

    public function testUserBanIsEffective(): void {
        $user = EntityFactory::makeUser();
        $user->addBan(new UserBan($user, 'foo', true, EntityFactory::makeUser()));

        $this->assertTrue($user->isBanned());
    }

    public function testExpiringUserBanIsEffective(): void {
        $user = EntityFactory::makeUser();
        $expires = new \DateTime('@'.time().' +1 hour');
        $user->addBan(new UserBan($user, 'foo', true, EntityFactory::makeUser(), $expires));

        $this->assertTrue($user->isBanned());
    }

    /**
     * @group time-sensitive
     */
    public function testExpiredUserBanIsIneffective(): void {
        $user = EntityFactory::makeUser();
        $expires = new \DateTime('@'.time().' +1 hour');
        $user->addBan(new UserBan($user, 'ofo', true, EntityFactory::makeUser(), $expires));

        sleep(7200); // 2 hours

        $this->assertFalse($user->isBanned());
    }

    /**
     * @dataProvider invalidEmailAddressProvider
     *
     * @param string $input
     */
    public function testNormalizeFailsOnInvalidEmailAddress($input): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email address');

        User::normalizeEmail($input);
    }

    public function unnormalizedUserProvider(): iterable {
        yield ['emma', 'Emma'];
        yield ['zach', 'zaCH'];
    }

    public function unnormalizedEmailAddressProvider(): iterable {
        yield ['pzm87i6bhxs2vzgm@gmail.com', 'PzM87.I6bhx.S2vzGm@gmail.com'];
        yield ['ays1hbjbpluzdivl@gmail.com', 'AyS1hBjbPLuZDiVl@googlemail.com'];
        yield ['pcpanmvb@gmail.com', 'pCPaNmvB+roHYEByv@gmail.com'];
        yield ['ag9kcmxicbmkec2tldicghc@gmail.com', 'aG9KC.mxIcBMk.ec2tldiCghc+SSOkIach3@gooGLEMail.com'];
        yield ['pCPaNmvBroHYEByv@example.com', 'pCPaNmvBroHYEByv@ExaMPle.CoM'];
    }

    public function invalidEmailAddressProvider(): iterable {
        yield ['gasg7a8.'];
        yield ['foo@examplenet@example.net'];
    }

    /**
     * @dataProvider provideSubmissionLinkDestinations
     */
    public function testSetAndGetSubmissionLinkDestinations(string $destination): void {
        $user = EntityFactory::makeUser();
        $this->assertSame(SubmissionLinkDestination::URL, $user->getSubmissionLinkDestination());
        $user->setSubmissionLinkDestination($destination);
        $this->assertSame($destination, $user->getSubmissionLinkDestination());
    }

    public function provideSubmissionLinkDestinations(): \Generator {
        foreach (SubmissionLinkDestination::OPTIONS as $destination) {
            yield [$destination];
        }
    }
}
