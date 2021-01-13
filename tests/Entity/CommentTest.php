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

        $this->assertCount(0, $comment->getUser()->getNotifications());
        $this->assertCount(1, $submission->getUser()->getNotifications());
    }

    public function testNewChildReplySendsNotifications(): void {
        $submission = new Submission('a', null, null, EntityFactory::makeForum(), EntityFactory::makeUser(), null);

        $parent = new Comment('a', EntityFactory::makeUser(), $submission, null);
        $child = new Comment('b', EntityFactory::makeUser(), $parent, null);

        $this->assertCount(0, $child->getUser()->getNotifications());
        $this->assertCount(1, $parent->getUser()->getNotifications());
    }

    public function testDoesNotSendNotificationsWhenReplyingToSelf(): void {
        $user = EntityFactory::makeUser();
        $submission = new Submission('a', null, null, EntityFactory::makeForum(), $user, null);

        $parent = new Comment('a', $user, $submission, null);
        new Comment('b', $user, $parent, null);

        $this->assertCount(0, $submission->getUser()->getNotifications());
    }
}
