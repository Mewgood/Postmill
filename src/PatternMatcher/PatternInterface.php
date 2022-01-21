<?php

declare(strict_types=1);

namespace App\PatternMatcher;

interface PatternInterface {
    public const TYPE_PHRASE = 1;
    public const TYPE_REGEX_FRAGMENT = 2;

    public function getPattern(): string;

    /**
     * @return int One of the TYPE_* constants
     */
    public function getPatternType(): int;
}
