<?php

namespace App\Tests\Security\Voter;

use App\Entity\CustomTextFlair;
use App\Entity\ForumBan;
use App\Entity\Moderator;
use App\Security\Voter\SubmissionVoter;
use App\Tests\Fixtures\Factory\EntityFactory;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @covers \App\Security\Voter\SubmissionVoter
 */
class SubmissionVoterTest extends VoterTestCase {
    protected function getVoter(): VoterInterface {
        return new SubmissionVoter($this->decisionManager);
    }

    public function testNonPrivilegedUserCannotDeleteOthersTrash(): void {
        $submission = EntityFactory::makeSubmission();
        $submission->trash();

        $token = $this->createToken(['ROLE_USER'], EntityFactory::makeUser());

        $this->expectRoleLookup('ROLE_ADMIN', $token);
        $this->assertDenied('purge', $submission, $token);
    }

    public function testAnonymousUserCannotFlair(): void {
        $submission = EntityFactory::makeSubmission();
        $token = new NullToken();

        $this->assertDenied('flair', $submission, $token);
    }

    public function testNonPrivilegedUserCannotFlair(): void {
        $submission = EntityFactory::makeSubmission();
        $token = $this->createToken(['ROLE_USER'], EntityFactory::makeUser());

        $this->assertDenied('flair', $submission, $token);
    }

    public function testModeratorCanFlair(): void {
        $user = EntityFactory::makeUser();
        $forum = EntityFactory::makeForum();
        $forum->addModerator(new Moderator($forum, $user));
        $submission = EntityFactory::makeSubmission($forum);
        $token = $this->createToken(['ROLE_USER'], $user);

        $this->assertGranted('flair', $submission, $token);
    }

    public function testCannotFlairWithThreeExistingFlairs(): void {
        $user = EntityFactory::makeUser();
        $forum = EntityFactory::makeForum();
        $forum->addModerator(new Moderator($forum, $user));
        $submission = EntityFactory::makeSubmission($forum);
        $submission->addFlair(new CustomTextFlair('1'));
        $submission->addFlair(new CustomTextFlair('2'));
        $submission->addFlair(new CustomTextFlair('3'));
        $token = $this->createToken(['ROLE_USER'], $user);

        $this->assertDenied('flair', $submission, $token);
    }

    public function testAdminCanFlair(): void {
        $user = EntityFactory::makeUser();
        $user->setAdmin(true);
        $submission = EntityFactory::makeSubmission();
        $token = $this->createToken(['ROLE_ADMIN', 'ROLE_USER'], $user);

        $this->assertGranted('flair', $submission, $token);
    }

    public function testUserCanPurgeOwnTrash(): void {
        $user = EntityFactory::makeUser();
        $submission = EntityFactory::makeSubmission(null, $user);
        $submission->trash();

        $token = $this->createToken(['ROLE_USER'], $user);

        $this->expectNoRoleLookup();
        $this->assertGranted('purge', $submission, $token);
    }

    public function testAdminCanPurgeOthersTrash(): void {
        $submission = EntityFactory::makeSubmission();
        $submission->trash();

        $token = $this->createToken(['ROLE_USER', 'ROLE_ADMIN'], EntityFactory::makeUser());

        $this->expectRoleLookup('ROLE_ADMIN', $token);
        $this->assertGranted('purge', $submission, $token);
    }

    public function testAnonymousUserCannotRemoveFlair(): void {
        $submission = EntityFactory::makeSubmission();
        $token = new NullToken();

        $this->assertDenied('remove_flair', $submission, $token);
    }

    public function testNonPrivilegedUserCannotRemoveFlair(): void {
        $submission = EntityFactory::makeSubmission();
        $token = $this->createToken(['ROLE_USER'], EntityFactory::makeUser());

        $this->assertDenied('remove_flair', $submission, $token);
    }

    public function testModeratorCanRemoveFlair(): void {
        $user = EntityFactory::makeUser();
        $forum = EntityFactory::makeForum();
        $forum->addModerator(new Moderator($forum, $user));
        $submission = EntityFactory::makeSubmission($forum);
        $token = $this->createToken(['ROLE_USER'], $user);

        $this->assertGranted('remove_flair', $submission, $token);
    }

    public function testAdminCanRemoveFlair(): void {
        $user = EntityFactory::makeUser();
        $user->setAdmin(true);
        $submission = EntityFactory::makeSubmission();
        $token = $this->createToken(['ROLE_ADMIN', 'ROLE_USER'], $user);

        $this->assertGranted('remove_flair', $submission, $token);
    }

    public function testCanVote(): void {
        $submission = EntityFactory::makeSubmission();

        $token = $this->createToken(['ROLE_USER'], EntityFactory::makeUser());

        $this->assertGranted('vote', $submission, $token);
    }

    public function testCannotVoteIfBanned(): void {
        $user = EntityFactory::makeUser();

        $submission = EntityFactory::makeSubmission();
        $forum = $submission->getForum();
        $forum->addBan(new ForumBan($forum, $user, 'reason', true, $submission->getUser()));

        $token = $this->createToken(['ROLE_USER'], $user);

        $this->assertDenied('vote', $submission, $token);
    }
}
