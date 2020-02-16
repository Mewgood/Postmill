<?php

namespace App\Tests\Validator\Constraints;

use App\Entity\Forum;
use App\Entity\ForumBan;
use App\Entity\User;
use App\Validator\Constraints\NotForumBanned;
use App\Validator\Constraints\NotForumBannedValidator;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\Constraints\NotForumBannedValidator
 */
class NotForumBannedValidatorTest extends ConstraintValidatorTestCase {
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function testNoViolationOnAnonymousUser(): void {
        $forum = new Forum('a', 'a', 'a', 'a');
        $this->validator->validate($forum, new NotForumBanned());

        $this->assertNoViolation();
    }

    public function testNoViolationOnEmptyTokenStorage(): void {
        $this->tokenStorage->setToken(null);

        $forum = new Forum('a', 'a', 'a', 'a');
        $this->validator->validate($forum, new NotForumBanned());

        $this->assertNoViolation();
    }

    public function testRaisesOnBannedUser(): void {
        $user = $this->login();
        $forum = new Forum('a', 'a', 'a', 'a');
        $forum->addBan(new ForumBan($forum, $user, 'a', true, new User('u', 'p')));

        $this->validator->validate($forum, new NotForumBanned());

        $this->buildViolation('forum.banned')
            ->setCode(NotForumBanned::FORUM_BANNED_ERROR)
            ->assertRaised();
    }

    public function testNoViolationOnExpiredBan(): void {
        $user = $this->login();
        $forum = new Forum('a', 'a', 'a', 'a');
        $forum->addBan(new ForumBan($forum, $user, 'a', true, new User('u', 'p'), new \DateTime('yesterday')));

        $this->validator->validate($forum, new NotForumBanned());

        $this->assertNoViolation();
    }

    public function testRaisesOnExpiringBan(): void {
        $user = $this->login();

        $forum = new Forum('a', 'a', 'a', 'a');
        $forum->addBan(new ForumBan($forum, $user, 'a', true, new User('a', 'p'), new \DateTime('tomorrow')));

        $this->validator->validate($forum, new NotForumBanned());

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

    protected function createValidator(): ConstraintValidator {
        $this->tokenStorage = new TokenStorage();
        $this->tokenStorage->setToken(new AnonymousToken('aa', 'anon.'));

        return new NotForumBannedValidator($this->tokenStorage);
    }

    private function login(): User {
        $user = new User('u', 'p');
        $this->tokenStorage->setToken(new UsernamePasswordToken($user, [], 'main'));

        return $user;
    }
}
