<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\UserBan;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface;

/**
 * @covers \App\Entity\UserBan
 * @group time-sensitive
 */
class UserBanTest extends TestCase {
    public function testConstruction(): void {
        $user = new User('u', 'p');
        $bannedBy = new User('u', 'p');
        $expires = new \DateTime('@'.time().' +600 seconds');

        $ban = new UserBan($user, 'sdfg', true, $bannedBy, $expires);

        $this->assertInstanceOf(UuidInterface::class, $ban->getId());
        $this->assertSame($user, $ban->getUser());
        $this->assertSame($bannedBy, $ban->getBannedBy());
        $this->assertSame('sdfg', $ban->getReason());
        $this->assertSame(time(), $ban->getTimestamp()->getTimestamp());
        $this->assertSame(time() + 600, $ban->getExpires()->getTimestamp());

        $this->assertCount(1, $user->getBans());
        $this->assertSame($ban, $user->getActiveBan());
    }

    public function testExpiration(): void {
        $expires = new \DateTime('@'.time().' +600 seconds');
        $ban = new UserBan(new User('u', 'p'), 'a', true, new User('u', 'p'), $expires);

        $this->assertFalse($ban->isExpired());
        sleep(601);
        $this->assertTrue($ban->isExpired());
    }

    public function testIndefiniteBanNeverExpires(): void {
        $ban = new UserBan(new User('u', 'p'), 'a', true, new User('u', 'p'));

        $this->assertFalse($ban->isExpired());
    }

    public function testUnbansCannotExpire(): void {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Unbans cannot have expiry times');

        new UserBan(new User('u', 'p'), 'a', false, new User('u', 'p'), new \DateTime());
    }
}
