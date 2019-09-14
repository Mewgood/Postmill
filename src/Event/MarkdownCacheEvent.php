<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched to build a hash key for Markdown context.
 */
final class MarkdownCacheEvent extends Event {
    private $hashData = [];

    public function getCacheKey(): string {
        ksort($this->hashData);

        return hash('sha256', json_encode($this->hashData));
    }

    public function addToCacheKey(string $key, ?string $value = null): void {
        $this->hashData[$key] = $value;
    }
}
