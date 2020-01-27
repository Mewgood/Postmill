<?php

namespace App\Tests\Entity;

use App\Entity\Comment;
use App\Entity\Forum;
use App\Entity\Submission;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\Comment
 */
class CommentTest extends TestCase {
    public function testNewTopLevelCommentSendsNotification(): void {
        $submission = new Submission('a', null, null, new Forum('a', 'a', 'a', 'a'), new User('u', 'p'), null);
        $comment = new Comment('a', new User('u', 'p'), $submission, null);

        $this->assertCount(0, $comment->getUser()->getNotifications());
        $this->assertCount(1, $submission->getUser()->getNotifications());
    }

    public function testNewChildReplySendsNotifications(): void {
        $submission = new Submission('a', null, null, new Forum('a', 'a', 'a', 'a'), new User('u', 'p'), null);

        $parent = new Comment('a', new User('u', 'p'), $submission, null);
        $child = new Comment('b', new User('u', 'p'), $parent, null);

        $this->assertCount(0, $child->getUser()->getNotifications());
        $this->assertCount(1, $parent->getUser()->getNotifications());
    }

    public function testDoesNotSendNotificationsWhenReplyingToSelf(): void {
        $user = new User('u', 'p');
        $submission = new Submission('a', null, null, new Forum('a', 'a', 'a', 'a'), $user, null);

        $parent = new Comment('a', $user, $submission, null);
        new Comment('b', $user, $parent, null);

        $this->assertCount(0, $submission->getUser()->getNotifications());
    }
}
