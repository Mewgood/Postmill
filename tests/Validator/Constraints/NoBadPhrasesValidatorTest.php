<?php

namespace App\Tests\Validator\Constraints;

use App\Entity\BadPhrase;
use App\Repository\BadPhraseRepository;
use App\Validator\Constraints\NoBadPhrases;
use App\Validator\Constraints\NoBadPhrasesValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\Constraints\NoBadPhrasesValidator
 */
class NoBadPhrasesValidatorTest extends ConstraintValidatorTestCase {
    public function testNonBannedPhraseWillNotRaise(): void {
        $this->validator->validate('food', new NoBadPhrases());

        $this->assertNoViolation();
    }

    /**
     * @dataProvider provideBannedWords
     */
    public function testBannedWordsWillRaise(string $bannedWord): void {
        $constraint = new NoBadPhrases();
        $this->validator->validate($bannedWord, $constraint);

        $this->buildViolation($constraint->message)
            ->setCode(NoBadPhrases::CONTAINS_BAD_PHRASE_ERROR)
            ->assertRaised();
    }

    public function testBannedTextInsideWordWillNotRaise(): void {
        $this->validator->validate('fee', new NoBadPhrases());

        $this->assertNoViolation();
    }

    public function testBannedRegexInsideWordWillRaise(): void {
        $constraint = new NoBadPhrases();
        $this->validator->validate('sadist', $constraint);

        $this->buildViolation($constraint->message)
            ->setCode(NoBadPhrases::CONTAINS_BAD_PHRASE_ERROR)
            ->assertRaised();
    }

    public function testDoesNotRaiseOnNull(): void {
        $this->validator->validate(null, new NoBadPhrases());

        $this->assertNoViolation();
    }

    /**
     * @dataProvider provideEmptyStringable
     */
    public function testDoesNotRaiseOnEmptyStringable($stringable): void {
        $this->validator->validate($stringable, new NoBadPhrases());

        $this->assertNoViolation();
    }

    public function testThrowsOnObjectWithoutToString(): void {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate((object) [], new NoBadPhrases());
    }

    public function testThrowsOnNonScalarNonStringableValue(): void {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate([], new NoBadPhrases());
    }

    protected function createValidator(): NoBadPhrasesValidator {
        /** @var BadPhraseRepository|\PHPUnit\Framework\MockObject\MockObject $repository */
        $repository = $this->createMock(BadPhraseRepository::class);
        $repository
            ->method('findAll')
            ->willReturn([
                new BadPhrase('tea', BadPhrase::TYPE_TEXT),
                new BadPhrase('coffee', BadPhrase::TYPE_TEXT),
                new BadPhrase('[bs]ad', BadPhrase::TYPE_REGEX),
            ]);

        return new NoBadPhrasesValidator($repository);
    }

    public function provideBannedWords(): iterable {
        yield ['tea'];
        yield ['coffee'];
        yield ['bad'];
        yield ['sad'];
    }

    public function provideEmptyStringable(): iterable {
        yield [''];
        yield [false];
        yield [new class() {
            public function __toString() {
                return '';
            }
        }];
    }
}
