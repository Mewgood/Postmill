<?php

declare(strict_types=1);

namespace App\PatternMatcher;

/**
 * @template TKey of int|string
 * @template TValue of PatternInterface
 */
interface PatternMatcherInterface {
    /**
     * Returns true if $subject is matched by any pattern, otherwise false.
     *
     * @param PatternCollectionInterface<TKey, TValue> $patterns
     */
    public function matches(
        string $subject,
        PatternCollectionInterface $patterns
    ): bool;

    /**
     * Find patterns that match the given subject.
     *
     * @param PatternCollectionInterface<TKey, TValue> $patterns
     * @return PatternCollectionInterface<TKey, TValue>
     */
    public function findMatching(
        string $subject,
        PatternCollectionInterface $patterns
    ): PatternCollectionInterface;
}
