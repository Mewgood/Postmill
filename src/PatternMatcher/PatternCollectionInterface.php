<?php

declare(strict_types=1);

namespace App\PatternMatcher;

/**
 * @template TKey of int|string
 * @template TValue of PatternInterface
 * @template-extends \Traversable<TKey, TValue>
 * @template-extends \ArrayAccess<TKey, TValue>
 */
interface PatternCollectionInterface extends \ArrayAccess, \Countable, \Traversable {
    public function getCacheKey(): string;

    /**
     * @return array<TKey, TValue>
     */
    public function toArray(): array;
}
