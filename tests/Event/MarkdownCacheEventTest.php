<?php

namespace App\Tests\Event;

use App\Event\MarkdownCacheEvent;
use PHPUnit\Framework\TestCase;

class MarkdownCacheEventTest extends TestCase {
    public function testContext(): void {
        $event = new MarkdownCacheEvent(['some' => 'context']);

        $this->assertEquals(['some' => 'context'], $event->getContext());
    }

    public function testCacheKeyGeneration(): void {
        $event = new MarkdownCacheEvent([]);
        $event->addToCacheKey('foo', 'bar');

        $this->assertSame(
            '7a38bf81f383f69433ad6e900d35b3e2385593f76a7b7ab5d4355b8ba41ee24b',
            $event->getCacheKey()
        );
    }

    public function testOrderOfAddingKeysDoesNotMatter(): void {
        $event1 = new MarkdownCacheEvent([]);
        $event1->addToCacheKey('a', '1');
        $event1->addToCacheKey('b', '2');
        $event1->addToCacheKey('c', '3');

        $event2 = new MarkdownCacheEvent([]);
        $event2->addToCacheKey('b', '2');
        $event2->addToCacheKey('c', '3');
        $event2->addToCacheKey('a', '1');

        $this->assertSame($event2->getCacheKey(), $event1->getCacheKey());
    }
}
