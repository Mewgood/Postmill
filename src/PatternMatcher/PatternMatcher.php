<?php

declare(strict_types=1);

namespace App\PatternMatcher;

/**
 * @template TKey of int|string
 * @template TValue of PatternInterface
 * @template-inherits PatternMatcherInterface<TKey, TValue>
 */
final class PatternMatcher implements PatternMatcherInterface {
    /**
     * @var RegexCompilerInterface
     */
    private $compiler;

    public function __construct(RegexCompilerInterface $compiler) {
        $this->compiler = $compiler;
    }

    public function matches(
        string $subject,
        PatternCollectionInterface $patterns
    ): bool {
        \set_error_handler(\Closure::fromCallable([self::class, 'handleError']));

        try {
            $regexes = $this->compiler->compileForFastMatch($patterns);

            foreach ($regexes as $regex) {
                if (preg_match($regex, $subject) === 1) {
                    return true;
                }
            }

            return false;
        } finally {
            \restore_error_handler();
        }
    }

    public function findMatching(
        string $subject,
        PatternCollectionInterface $patterns
    ): PatternCollectionInterface {
        \set_error_handler(\Closure::fromCallable([self::class, 'handleError']));
        $matched = [];

        try {
            $regexes = $this->compiler->compileForLookup($patterns);

            foreach ($regexes as $key => $regex) {
                if (preg_match($regex, $subject) === 1) {
                    $matched[$key] = $patterns[$key];
                }
            }

            return new PatternCollection($matched);
        } finally {
            \restore_error_handler();
        }
    }

    private static function handleError(
        int $errno,
        string $errstr,
        string $errfile,
        int $errline
    ) {
        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
}
