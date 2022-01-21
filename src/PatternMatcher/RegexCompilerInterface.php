<?php

declare(strict_types=1);

namespace App\PatternMatcher;

/**
 * @template TKey of int|string
 * @template TValue of PatternInterface
 */
interface RegexCompilerInterface {
    /**
     * Create optimised PCRE regexes for boolean matching against a string.
     *
     * @return array<string> An array of PCRE compatible regular expressions
     */
    public function compileForFastMatch(
        PatternCollectionInterface $patterns
    ): array;

    /**
     * Create individual PCRE regexes for identifying which pattern matches a
     * string.
     *
     * Each regex must have a key corresponding to that of the pattern
     * collection entry that generated it.
     *
     * @param PatternCollectionInterface<TKey, TValue> $patterns
     * @return array<TKey, string>
     */
    public function compileForLookup(
        PatternCollectionInterface $patterns
    ): array;
}
