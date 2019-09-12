<?php

namespace App\Tests\DataObject;

use App\DataObject\CommentData;
use App\Entity\Comment;
use App\Entity\Submission;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ClockMock;

/**
 * @group time-sensitive
 */
class CommentDataTest extends TestCase {
    /**
     * @var Comment|MockObject
     */
    private $comment;

    public static function setUpBeforeClass(): void {
        ClockMock::register(CommentData::class);
    }

    protected function setUp(): void {
        $this->comment = $this->getMockBuilder(Comment::class)
            ->setMethods(['getSubmission', 'getTimestamp', 'getVotes', 'getUser', 'getReplyCount'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->comment
            ->method('getSubmission')
            ->willReturn($this->createMock(Submission::class));

        $this->comment
            ->method('getUser')
            ->willReturn($this->createMock(User::class));

        $this->comment
            ->method('getTimestamp')
            ->willReturn(new \DateTime('@'.time()));

        $this->comment
            ->method('getVotes')
            ->willReturn(new ArrayCollection());

        $this->comment
            ->method('getReplyCount')
            ->willReturn(0);

        $this->comment->setBody('foo');
    }

    public function testUpdate(): void {
        $data = new CommentData($this->comment);
        $data->setBody('bar');
        $data->updateComment($this->comment, $this->comment->getUser());

        $this->assertEquals(new \DateTime('@'.time()), $this->comment->getEditedAt());
        $this->assertFalse($this->comment->isModerated());

        sleep(5);

        $data->setBody('baz');
        $data->updateComment($this->comment, $this->createMock(User::class));

        $this->assertEquals(new \DateTime('@'.time()), $this->comment->getEditedAt());
        $this->assertTrue($this->comment->isModerated());
    }
}
