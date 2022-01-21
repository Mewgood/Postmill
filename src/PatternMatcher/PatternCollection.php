<?php

declare(strict_types=1);

namespace App\PatternMatcher;

/**
 * @template TKey of int|string
 * @template TValue of PatternInterface
 * @template-implements PatternCollectionInterface<TKey, TValue>
 */
final class PatternCollection implements \IteratorAggregate, PatternCollectionInterface {
    /**
     * @var array<TKey, TValue>
     */
    private $entries;

    /**
     * @var string|null
     */
    private $cachedCacheKey = null;

    /**
     * @param array<TKey, TValue> $entries
     */
    public function __construct(array $entries = []) {
        $this->entries = $entries;
    }

    public function offsetExists($offset): bool {
        return isset($this->entries[$offset]);
    }

    public function offsetGet($offset): PatternInterface {
        if (!isset($this->entries[$offset])) {
            throw new \OutOfBoundsException('No such entry');
        }

        return $this->entries[$offset];
    }

    public function offsetSet($offset, $value): void {
        throw new \BadMethodCallException('The collection cannot be altered');
    }

    public function offsetUnset($offset): void {
        throw new \BadMethodCallException('The collection cannot be altered');
    }

    public function count(): int {
        return \count($this->entries);
    }

    public function getIterator(): \Iterator {
        return new \ArrayIterator($this->entries);
    }

    public function getCacheKey(): string {
        if ($this->cachedCacheKey === null) {
            $digest = hash_init('sha256');
            foreach ($this->entries as $key => $entry) {
                $value = str_replace('|', '\|', $entry->getPattern());
                hash_update($digest, "$key|$value|");
            }

            $this->cachedCacheKey = hash_final($digest);
        }

        return $this->cachedCacheKey;
    }

    public function toArray(): array {
        return $this->entries;
    }
}
