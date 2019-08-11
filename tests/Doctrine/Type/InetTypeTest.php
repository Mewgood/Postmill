<?php

namespace App\Tests\Doctrine\Type;

use App\Doctrine\Type\InetType;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;

class InetTypeTest extends TestCase {
    /**
     * @var Type
     */
    private $type;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|PostgreSqlPlatform
     */
    private $platform;

    public static function setUpBeforeClass() {
        if (!Type::hasType('inet')) {
            Type::addType('inet', InetType::class);
        }
    }

    protected function setUp() {
        $this->type = Type::getType('inet');
        $this->platform = $this->createMock(PostgreSqlPlatform::class);
    }

    /**
     * @dataProvider inetProvider
     */
    public function testCanConvertValueToDatabaseType($value, $expected) {
        $this->assertSame(
            $expected,
            $this->type->convertToDatabaseValue($value, $this->platform)
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDoesNotWorkWithNonPostgresPlatforms() {
        /** @var \PHPUnit\Framework\MockObject\MockObject|MySqlPlatform $platform */
        $platform = $this->createMock(MySqlPlatform::class);

        $this->type->convertToDatabaseValue('::1', $platform);
    }

    public function inetProvider() {
        yield ['::1', '::1'];
        yield ['::1/128', '::1/128'];
        yield ['aaaa::aaaa/128', 'aaaa::aaaa/128'];
//        yield ['aaaa::aaaa/16', 'aaaa::/16'];
        yield ['127.0.0.1/32', '127.0.0.1/32'];
        yield ['127.255.0.0/16', '127.255.0.0/16'];
        yield [null, null];
    }
}
