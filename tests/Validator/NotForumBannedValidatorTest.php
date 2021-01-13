<?php

namespace App\Tests\Validator;

use App\Entity\Forum;
use App\Entity\ForumBan;
use App\Entity\User;
use App\Tests\Fixtures\Factory\EntityFactory;
use App\Validator\NotForumBanned;
use App\Validator\NotForumBannedValidator;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\NotForumBannedValidator
 */
class NotForumBannedValidatorTest extends ConstraintValidatorTestCase {
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var Forum
     */
    private $forum;

    protected function setUp(): void {
        parent::setUp();

        $this->forum = EntityFactory::makeForum();
    }

    protected function createValidator(): ConstraintValidator {
        $this->tokenStorage = new TokenStorage();
        $this->tokenStorage->setToken(new AnonymousToken('aa', 'anon.'));

        return new NotForumBannedValidator($this->tokenStorage);
    }

    public function testNoViolationOnAnonymousUser(): void {
        $this->validator->validate($this->forum, new NotForumBanned());

        $this->assertNoViolation();
    }

    public function testNoViolationOnEmptyTokenStorage(): void {
        $this->tokenStorage->setToken(null);

        $this->validator->validate($this->forum, new NotForumBanned());

        $this->assertNoViolation();
    }

    public function testRaisesOnBannedUser(): void {
        $user = $this->login();
        $this->forum->addBan(new ForumBan($this->forum, $user, 'a', true, EntityFactory::makeUser()));

        $this->validator->validate($this->forum, new NotForumBanned());

        $this->buildViolation('forum.banned')
            ->setCode(NotForumBanned::FORUM_BANNED_ERROR)
            ->assertRaised();
    }

    public function testNoViolationOnExpiredBan(): void {
        $user = $this->login();
        $this->forum->addBan(new ForumBan($this->forum, $user, 'a', true, EntityFactory::makeUser(), new \DateTime('yesterday')));

        $this->validator->validate($this->forum, new NotForumBanned());

        $this->assertNoViolation();
    }

    public function testRaisesOnExpiringBan(): void {
        $user = $this->login();

        $this->forum->addBan(new ForumBan($this->forum, $user, 'a', true, EntityFactory::makeUser(), new \DateTime('tomorrow')));

        $this->validator->validate($this->forum, new NotForumBanned());

        $this->buildViolation('forum.banned')
            ->setCode(NotForumBanned::FORUM_BANNED_ERROR)
            ->assertRaised();
    }

    public function testNoViolationOnNullForum(): void {
        $this->login();
        $data = (object) ['forum' => null];

        $this->validator->validate($data, new NotForumBanned(['forumPath' => 'forum']));

        $this->assertNoViolation();
    }

    private function login(): User {
        $user = EntityFactory::makeUser();
        $this->tokenStorage->setToken(new UsernamePasswordToken($user, [], 'main'));

        return $user;
    }
}
