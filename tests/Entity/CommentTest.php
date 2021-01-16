<?php

namespace App\Tests\Entity;

use App\Entity\Comment;
use App\Entity\Submission;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\Comment
 */
class CommentTest extends TestCase {
    public function testNewTopLevelCommentSendsNotification(): void {
        $submission = new Submission('a', null, null, EntityFactory::makeForum(), EntityFactory::makeUser(), null);
        $comment = new Comment('a', EntityFactory::makeUser(), $submission, null);

        $this->assertEquals(0, $comment->getUser()->getNotificationCount());
        $this->assertEquals(1, $submission->getUser()->getNotificationCount());
    }

    public function testNewChildReplySendsNotifications(): void {
        $submission = new Submission('a', null, null, EntityFactory::makeForum(), EntityFactory::makeUser(), null);

        $parent = new Comment('a', EntityFactory::makeUser(), $submission, null);
        $child = new Comment('b', EntityFactory::makeUser(), $parent, null);

        $this->assertEquals(0, $child->getUser()->getNotificationCount());
        $this->assertEquals(1, $parent->getUser()->getNotificationCount());
    }

    public function testDoesNotSendNotificationsWhenReplyingToSelf(): void {
        $user = EntityFactory::makeUser();
        $submission = new Submission('a', null, null, EntityFactory::makeForum(), $user, null);

        $parent = new Comment('a', $user, $submission, null);
        new Comment('b', $user, $parent, null);

        $this->assertEquals(0, $submission->getUser()->getNotificationCount());
    }
}
