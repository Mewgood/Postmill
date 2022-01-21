<?php

declare(strict_types=1);

namespace App\PatternMatcher;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @todo handle max fragment length
 */
final class RegexCompiler implements RegexCompilerInterface {
    private const DELIMITER = '@';
    private const FLAGS = 'u';
    private const REGEX_START = self::DELIMITER;
    private const REGEX_END = self::DELIMITER.self::FLAGS;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $maxRegexLength;

    public function __construct(?LoggerInterface $logger) {
        $this->logger = $logger ?? new NullLogger();
        $this->maxRegexLength = 0x7ffe -
            \strlen(self::REGEX_START) -
            \strlen(self::REGEX_END);
    }

    public function compileForFastMatch(
        PatternCollectionInterface $patterns
    ): array {
        $endIndex = \count($patterns) - 1;

        if ($endIndex < 0) {
            return [];
        }

        $regexes = [];
        $regex = self::REGEX_START;
        $i = 0;

        foreach ($patterns as $pattern) {
            $part = '(?:'.self::makeFragment($pattern).')';

            $tentativeRegexLength =
                \strlen($regex) + \strlen($part) +
                ($i === $endIndex ? \strlen(self::REGEX_END) : 0) +
                ($regex !== self::REGEX_START ? 1 : 0);

            // Start a new regex if appending this fragment to the current one
            // results in a regex larger than the max allowed size
            if ($tentativeRegexLength > $this->maxRegexLength) {
                $regex .= self::REGEX_END;
                $regexes[] = $regex;
                $regex = self::REGEX_START;
            }

            if ($regex !== self::REGEX_START) {
                $part = "|$part";
            }

            $regex .= $part;
            $i += 1;
        }

        $regex .= self::REGEX_END;
        $regexes[] = $regex;

        $this->logger->debug(self::class.': Regexes built', [
            'patterns' => $patterns->toArray(),
            'regexes' => $regexes,
        ]);

        return $regexes;
    }

    public function compileForLookup(
        PatternCollectionInterface $patterns
    ): array {
        $regexes = [];
        foreach ($patterns as $pattern) {
            $regexes[] = self::REGEX_START.
                self::makeFragment($pattern).
                self::REGEX_END;
        }

        return $regexes;
    }

    private static function makeFragment(PatternInterface $pattern): string {
        switch ($pattern->getPatternType()) {
        case PatternInterface::TYPE_PHRASE:
            return '(?i)(?<!\w)'.
                preg_quote($pattern->getPattern(), self::DELIMITER).
                '(?!\w)';

        case PatternInterface::TYPE_REGEX_FRAGMENT:
            $part = addcslashes($pattern->getPattern(), self::DELIMITER);
            if (preg_match('@\(\?[A-Za-z]*?x[A-Za-z]*\).*[^\\\\]#@', $part)) {
                // handle (?x) with comment
                $part .= "\n";
            }
            return $part;

        default:
            throw new \DomainException('Unknown pattern type');
        }
    }
}
