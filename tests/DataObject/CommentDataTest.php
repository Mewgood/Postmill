<?php

namespace App\Tests\DataObject;

use App\DataObject\CommentData;
use App\Entity\Comment;
use App\Entity\Forum;
use App\Entity\Submission;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ClockMock;

/**
 * @covers \App\DataObject\CommentData
 * @group time-sensitive
 */
class CommentDataTest extends TestCase {
    /**
     * @var Comment
     */
    private $comment;

    public static function setUpBeforeClass(): void {
        ClockMock::register(CommentData::class);
    }

    protected function setUp(): void {
        $forum = new Forum('a', 'a', 'a', 'a');
        $user = new User('u', 'p');
        $parent = new Submission('a', null, null, $forum, $user, null);

        $this->comment = new Comment('foo', new User('u', 'p'), $parent, null);
    }

    public function testUpdate(): void {
        $data = new CommentData($this->comment);
        $data->setBody('bar');
        $data->updateComment($this->comment, $this->comment->getUser());

        $this->assertSame(time(), $this->comment->getEditedAt()->getTimestamp());
        $this->assertFalse($this->comment->isModerated());

        sleep(5);

        $data->setBody('baz');
        $data->updateComment($this->comment, new User('u', 'p'));

        $this->assertSame(time(), $this->comment->getEditedAt()->getTimestamp());
        $this->assertTrue($this->comment->isModerated());
    }
}
