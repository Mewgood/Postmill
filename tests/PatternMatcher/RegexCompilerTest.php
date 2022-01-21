<?php

declare(strict_types=1);

namespace App\Tests\PatternMatcher;

use App\PatternMatcher\PatternCollection;
use App\PatternMatcher\PatternCollectionInterface;
use App\PatternMatcher\PatternInterface;
use App\PatternMatcher\RegexCompiler;
use App\Tests\Fixtures\Pattern;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\PatternMatcher\RegexCompiler
 */
final class RegexCompilerTest extends TestCase {
    /**
     * @var RegexCompiler
     */
    private $compiler;

    protected function setUp(): void {
        $this->compiler = new RegexCompiler(null);
    }

    /**
     * @param array<PatternInterface> $expected
     * @dataProvider provideSubjects
     */
    public function testCompileForFastMatch(
        string $subject,
        array $expected,
        PatternCollection $patterns
    ): void {
        $regexes = $this->compiler->compileForFastMatch($patterns);
        $match = \count($expected) > 0;

        $matching = array_filter($regexes, function (string $regex) use ($subject) {
            return preg_match($regex, $subject) === 1;
        }) !== [];

        $this->assertSame($match, $matching);
    }

    /**
     * @param array<PatternCollectionInterface> $expected
     * @dataProvider provideSubjects
     */
    public function testCompileForLookup(
        string $subject,
        array $expected,
        PatternCollection $patterns
    ): void {
        $regexes = $this->compiler->compileForLookup($patterns);

        $matched = array_filter($regexes, function (string $regex) use ($subject) {
            return preg_match($regex, $subject) === 1;
        });
        $matched = array_combine(
            array_keys($matched),
            array_map(function (int $key) use ($patterns) {
                return $patterns[$key];
            }, array_keys($matched)),
        );

        $this->assertSame($expected, $matched);
    }

    /**
     * @return \Generator<array{string, array<PatternInterface>, PatternCollectionInterface}>
     */
    public function provideSubjects(): \Generator {
        $patterns = new PatternCollection([
            Pattern::phrase('tea'),
            Pattern::phrase('coffee'),
            Pattern::regexFragment('[bs]ad'),
            Pattern::regexFragment('(?x) should # not break'),
            Pattern::phrase('@juice'),
        ]);

        yield 'empty string' => ['', [], $patterns];
        yield 'no match' => ['food', [], $patterns];
        yield 'phrase match 1' => ['tea', [0 => $patterns[0]], $patterns];
        yield 'phrase match 2' => ['coffee', [1 => $patterns[1]], $patterns];
        yield 'substring inside word does not match' => ['fee', [], $patterns];
        yield 'regex match 1' => ['bad', [2 => $patterns[2]], $patterns];
        yield 'regex match 2' => ['sad', [2 => $patterns[2]], $patterns];
        yield 'regex supports /x flag' => ['should', [3 => $patterns[3]], $patterns];
        yield 'regex matches inside word' => ['sadist', [2 => $patterns[2]], $patterns];
        // https://gitlab.com/postmill/Postmill/-/issues/83
        yield 'phrase starting with word char will match itself' => ['@juice', [4 => $patterns[4]], $patterns];
        yield 'multiple matches' => ['sad tea', [0 => $patterns[0], 2 => $patterns[2]], $patterns];

        $patterns = new PatternCollection([]);
        yield 'empty pattern collection' => ['empty', [], $patterns];
    }

    public function testLongPatternsCauseFastMatchRegexToSplit(): void {
        $regexes = $this->compiler->compileForFastMatch(new PatternCollection([
            Pattern::phrase(str_repeat('a', 20000)),
            Pattern::regexFragment(str_repeat('b', 20000)),
        ]));

        $this->assertCount(2, $regexes);
        $this->assertMatchesRegularExpression($regexes[0], str_repeat('a', 20000));
        $this->assertMatchesRegularExpression($regexes[1], str_repeat('b', 20000));
    }
}
