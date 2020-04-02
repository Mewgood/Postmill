<?php

namespace App\Tests\Entity;

use App\Entity\BadPhrase;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\BadPhrase
 */
class BadPhraseTest extends TestCase {
    /**
     * @dataProvider provideGoodRegexes
     * @doesNotPerformAssertions
     */
    public function testCanConstructWithGoodRegex(string $regex): void {
        new BadPhrase($regex, BadPhrase::TYPE_REGEX);
    }

    /**
     * @dataProvider provideBadRegexes
     */
    public function testCannotConstructWithBadRegex(string $regex): void {
        $this->expectException(\DomainException::class);

        new BadPhrase($regex, BadPhrase::TYPE_REGEX);
    }

    public function provideGoodRegexes(): iterable {
        yield ['.'];
        yield ['foo(bar)?'];
    }

    public function provideBadRegexes(): iterable {
        yield [''];
        yield ['foo('];
    }
}
