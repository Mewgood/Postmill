<?php

namespace App\Tests\Validator;

use App\PatternMatcher\PatternCollectionInterface;
use App\PatternMatcher\PatternMatcherInterface;
use App\Repository\BadPhraseRepository;
use App\Validator\IpWithCidr;
use App\Validator\NoBadPhrases;
use App\Validator\NoBadPhrasesValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\NoBadPhrasesValidator
 */
class NoBadPhrasesValidatorTest extends ConstraintValidatorTestCase {
    /**
     * @var PatternMatcherInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private $matcher;

    /**
     * @var BadPhraseRepository&\PHPUnit\Framework\MockObject\MockObject
     */
    private $badPhrases;

    protected function setUp(): void {
        $this->matcher = $this->createMock(PatternMatcherInterface::class);
        $this->badPhrases = $this->createMock(BadPhraseRepository::class);

        parent::setUp();
    }

    protected function createValidator(): NoBadPhrasesValidator {
        return new NoBadPhrasesValidator($this->badPhrases, $this->matcher);
    }

    public function testMatchingInputWillRaise(): void {
        $this->expectPatternMatch('fly', true);

        $constraint = new NoBadPhrases();
        $this->validator->validate('fly', $constraint);

        $this->buildViolation($constraint->message)
            ->setCode(NoBadPhrases::CONTAINS_BAD_PHRASE_ERROR)
            ->assertRaised();
    }

    public function testNonMatchingInputWillNotRaise(): void {
        $this->expectPatternMatch('bee', false);

        $this->validator->validate('bee', new NoBadPhrases());

        $this->assertNoViolation();
    }

    /**
     * @dataProvider provideEmptyInputs
     * @param \Stringable|scalar|null $emptyInput
     */
    public function testEmptyInputWillNotRaise($emptyInput): void {
        $this->expectNoPatternMatch();

        $this->validator->validate($emptyInput, new NoBadPhrases());

        $this->assertNoViolation();
    }

    public function testThrowsOnNonScalarNonStringableValue(): void {
        $this->expectNoPatternMatch();

        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate([], new NoBadPhrases());
    }

    public function testThrowsOnWrongConstraintType(): void {
        $this->expectNoPatternMatch();

        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate('aa', new IpWithCidr());
    }

    /**
     * @return iterable<array{\Stringable|scalar|null}>
     */
    public function provideEmptyInputs(): iterable {
        yield [null];
        yield [''];
        yield [false];
        yield [new class() {
            public function __toString(): string {
                return '';
            }
        }];
    }

    private function expectPatternMatch(string $subject, bool $result): void {
        $patternCollection = $this->createMock(PatternCollectionInterface::class);

        $this->badPhrases
            ->expects($this->once())
            ->method('toPatternCollection')
            ->willReturn($patternCollection);

        $this->matcher
            ->expects($this->once())
            ->method('matches')
            ->with($subject, $this->identicalTo($patternCollection))
            ->willReturn($result);
    }

    private function expectNoPatternMatch(): void {
        $this->badPhrases
            ->expects($this->never())
            ->method('toPatternCollection');

        $this->matcher
            ->expects($this->never())
            ->method('matches');
    }
}
