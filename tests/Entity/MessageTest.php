<?php

namespace App\Tests\Entity;

use App\Entity\Message;
use App\Entity\MessageThread;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\Message
 */
class MessageTest extends TestCase {
    /**
     * @var User
     */
    private $sender;

    /**
     * @var User
     */
    private $receiver;

    protected function setUp(): void {
        $this->sender = new User('u', 'p');
        $this->receiver = new User('u', 'p');
    }

    public function testNewMessagesSendNotifications(): void {
        $thread = new MessageThread($this->sender, $this->receiver);

        new Message($thread, $this->sender, 'c', null);
        new Message($thread, $this->receiver, 'd', null);

        $this->assertCount(1, $this->receiver->getNotifications());
        $this->assertCount(1, $this->sender->getNotifications());
    }

    public function testNonParticipantsCannotAccessThread(): void {
        $thread = new MessageThread($this->sender, $this->receiver);

        $this->assertFalse($thread->userIsParticipant(new User('u', 'p')));
    }

    public function testBothParticipantsCanAccessOwnThread(): void {
        $thread = new MessageThread($this->sender, $this->receiver);

        $this->assertTrue($thread->userIsParticipant($this->receiver));
        $this->assertTrue($thread->userIsParticipant($this->sender));
    }
}
