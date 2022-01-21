<?php

declare(strict_types=1);

namespace App\Tests\Fixtures;

use App\PatternMatcher\PatternInterface;

class Pattern implements PatternInterface {
    /**
     * @var string
     */
    private $pattern;

    /**
     * @var int
     */
    private $type;

    public function __construct(string $pattern, int $type) {
        $this->pattern = $pattern;
        $this->type = $type;
    }

    public function getPattern(): string {
        return $this->pattern;
    }

    public function getPatternType(): int {
        return $this->type;
    }

    public static function phrase(string $phrase): self {
        return new self($phrase, PatternInterface::TYPE_PHRASE);
    }

    public static function regexFragment(string $regexFragment): self {
        return new self($regexFragment, PatternInterface::TYPE_REGEX_FRAGMENT);
    }
}
