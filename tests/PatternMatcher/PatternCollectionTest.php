<?php

declare(strict_types=1);

namespace App\Tests\PatternMatcher;

use App\PatternMatcher\PatternCollection;
use App\Tests\Fixtures\Pattern;
use BadMethodCallException;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\PatternMatcher\PatternCollection
 */
final class PatternCollectionTest extends TestCase {
    public function testOffsetExists(): void {
        $collection = new PatternCollection([
            'a' => Pattern::phrase('foo'),
            0 => Pattern::phrase('bar'),
        ]);

        $this->assertArrayHasKey('a', $collection);
        $this->assertArrayHasKey(0, $collection);
        $this->assertArrayNotHasKey('b', $collection);
    }

    public function testOffsetGet(): void {
        $collection = new PatternCollection([
            'a' => Pattern::phrase('foo'),
            0 => Pattern::phrase('bar'),
        ]);

        $this->assertSame('foo', $collection['a']->getPattern());
        $this->assertSame('bar', $collection[0]->getPattern());
    }

    public function testOffsetGetNonexistent(): void {
        $this->expectException(OutOfBoundsException::class);

        (new PatternCollection([]))[0];
    }

    public function testOffsetSet(): void {
        $this->expectException(BadMethodCallException::class);

        $collection = new PatternCollection();

        $collection['a'] = Pattern::phrase('foo');
    }

    public function testOffsetUnset(): void {
        $this->expectException(BadMethodCallException::class);

        $collection = new PatternCollection(['a' => Pattern::phrase('foo')]);

        unset($collection['a']);
    }

    public function testCacheKey(): void {
        $collection = new PatternCollection([
            4 => Pattern::phrase('foo'),
            'twenty' => Pattern::regexFragment('|'),
            69 => Pattern::phrase('bar'),
        ]);

        $this->assertSame(
            hash('sha256', '4|foo|twenty|\||69|bar|'),
            $collection->getCacheKey(),
        );
    }
}
