<?php

namespace App\Tests\Entity;

use App\Entity\Contracts\Votable;
use App\Entity\Exception\BannedFromForumException;
use App\Entity\Forum;
use App\Entity\ForumBan;
use App\Entity\Image;
use App\Entity\Submission;
use App\Entity\User;
use App\Entity\UserFlags;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ClockMock;

/**
 * @covers \App\Entity\Submission
 * @group time-sensitive
 */
class SubmissionTest extends TestCase {
    public static function setUpBeforeClass(): void {
        ClockMock::register(Submission::class);
    }

    public function testSoftDelete(): void {
        $forum = new Forum('a', 'a', 'a', 'a');
        $user = new User('u', 'p');

        $submission = new Submission(
            'The title',
            'http://www.example.com',
            'the body',
            $forum,
            $user,
            '127.0.0.1'
        );
        $submission->setImage(new Image('foo.png', random_bytes(32), null, null));
        $submission->setSticky(true);
        $submission->setUserFlag(UserFlags::FLAG_ADMIN);

        $submission->softDelete();

        $this->assertEmpty($submission->getTitle());
        $this->assertNull($submission->getBody());
        $this->assertNull($submission->getImage());
        $this->assertNull($submission->getUrl());
        $this->assertFalse($submission->isSticky());
        $this->assertSame(Submission::MEDIA_URL, $submission->getMediaType());
        $this->assertSame(UserFlags::FLAG_NONE, $submission->getUserFlag());
        $this->assertSame(Submission::VISIBILITY_SOFT_DELETED, $submission->getVisibility());
    }

    public function testCannotSetBogusUserFlag(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Bad user flag 'poopy'");

        $forum = new Forum('a', 'a', 'a', 'a');
        $user = new User('u', 'p');
        $submission = new Submission('a', null, null, $forum, $user, null);
        $submission->setUserFlag('poopy');
    }

    public function testCannotCreateSubmissionWithInvalidIpAddress(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid IP address 'in:va:li:d'");

        $forum = new Forum('a', 'a', 'a', 'a');
        $user = new User('u', 'p');
        new Submission('a', null, null, $forum, $user, 'in:va:li:d');
    }

    public function testCannotSetMediaTypeImageOnSubmissionWithUrl(): void {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Submission with URL cannot have image as media type');

        /** @var Submission $submission */
        $forum = new Forum('a', 'a', 'a', 'a');
        $user = new User('u', 'p');
        $submission = new Submission('a', null, null, $forum, $user, null);

        $submission->setUrl('http://www.example.com');
        $submission->setMediaType(Submission::MEDIA_IMAGE);
    }

    /**
     * @dataProvider constructorArgsProvider
     */
    public function testConstructor(string $title, ?string $url, ?string $body, Forum $forum, User $user, ?string $ip): void {
        $submission = new Submission($title, $url, $body, $forum, $user, $ip);

        $this->assertSame($title, $submission->getTitle());
        $this->assertSame($url, $submission->getUrl());
        $this->assertSame($body, $submission->getBody());
        $this->assertSame($forum, $submission->getForum());
        $this->assertSame($user, $submission->getUser());
        $this->assertSame($ip, $submission->getIp());
        $this->assertSame(time() + 1800, $submission->getRanking());
        $this->assertCount(1, $submission->getVotes());
        $this->assertSame($ip, $submission->getVotes()->first()->getIp());
        $this->assertSame($user, $submission->getVotes()->first()->getUser('u', 'p'));
    }

    public function testBannedUserCannotCreateSubmission(): void {
        $user = new User('u', 'p');
        $forum = new Forum('a', 'a', 'a', 'a');
        $forum->addBan(new ForumBan($forum, $user, 'a', true, new User('u', 'p')));

        $this->expectException(BannedFromForumException::class);

        new Submission('a', null, 'a', $forum, $user, null);
    }

    public function testBannedUserCannotAddVote(): void {
        $forum = new Forum('a', 'a', 'a', 'a');
        $user = new User('u', 'p');
        $forum->addBan(new ForumBan($forum, $user, 'a', true, new User('u', 'p')));

        $submission = new Submission('a', null, 'a', $forum, new User('u', 'p'), null);

        $this->expectException(BannedFromForumException::class);

        $submission->addVote($submission->createVote(Votable::VOTE_UP, $user, '::1'));
    }

    public function constructorArgsProvider(): iterable {
        $forum = new Forum('a', 'a', 'a', 'a');
        $user = new User('u', 'p');
        $url = 'http://example.com';

        yield ['title', $url, 'body', $forum, $user, '::1'];
        yield ['title', null, 'body', $forum, $user, '::1'];
        yield ['title', $url, null, $forum, $user, '::1'];
        yield ['title', null, null, $forum, $user, null];
    }
}
