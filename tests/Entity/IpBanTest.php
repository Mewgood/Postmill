<?php

namespace App\Tests\Entity;

use App\Entity\IpBan;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\IpBan
 * @group time-sensitive
 */
class IpBanTest extends TestCase {
    public function testConstruction(): void {
        $user = new User('u', 'p');
        $bannedBy = new User('u', 'p');
        $ban = new IpBan('123.123.123.123', 'aaa', $user, $bannedBy, new \DateTime('@'.time().' +600 seconds'));

        $this->assertSame('123.123.123.123', $ban->getIp());
        $this->assertSame('aaa', $ban->getReason());
        $this->assertSame($user, $ban->getUser());
        $this->assertSame($bannedBy, $ban->getBannedBy());
        $this->assertSame(time(), $ban->getTimestamp()->getTimestamp());
        $this->assertSame(time() + 600, $ban->getExpires()->getTimestamp());
    }

    public function testCannotConstructWithInvalidIp(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$ip must be valid IP with optional CIDR range');

        new IpBan('256.256.256.256', 'a', null, new User('u', 'p'));
    }

    /**
     * @dataProvider provideInvalidIpsWithMasks
     */
    public function testCannotConstructWithInvalidCidr(string $invalidIp): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid CIDR mask');

        new IpBan($invalidIp, 'a', null, new User('u', 'p'));
    }

    /**
     * @dataProvider provideIpsAndIpRanges
     */
    public function testIsRangeBan(string $ip, bool $isRange): void {
        $ban = new IpBan($ip, 'a', null, new User('u', 'p'));

        $this->assertSame($isRange, $ban->isRangeBan());
    }

    public function provideIpsAndIpRanges(): iterable {
        yield ['123.123.123.123', false];
        yield ['123.123.123.123/32', false];
        yield ['123.123.123.123/31', true];
        yield ['1234:1234::1234', false];
        yield ['1234:1234::1234/128', false];
        yield ['1234:1234::1234/127', true];
    }

    public function provideInvalidIpsWithMasks(): iterable {
        yield ['1.1.1.1/33'];
        yield ['1.1.1.1/335782317590127581273589012375890127389012357890123578902357890235789025378901235789012357905789012537890'];
        yield ['2001:4:4:4::4/129'];
        yield ['2001:4:4:4::4/-1'];
    }
}
