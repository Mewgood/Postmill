<?php

namespace App\Validator;

use App\PatternMatcher\PatternMatcherInterface;
use App\Repository\BadPhraseRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class NoBadPhrasesValidator extends ConstraintValidator {
    /**
     * @var BadPhraseRepository
     */
    private $badPhrases;

    /**
     * @var PatternMatcherInterface
     */
    private $matcher;

    public function __construct(
        BadPhraseRepository $badPhrases,
        PatternMatcherInterface $matcher
    ) {
        $this->badPhrases = $badPhrases;
        $this->matcher = $matcher;
    }

    public function validate($value, Constraint $constraint): void {
        if ($value === null) {
            return;
        }

        if (!$constraint instanceof NoBadPhrases) {
            throw new UnexpectedTypeException($constraint, NoBadPhrases::class);
        }

        if (!is_scalar($value) && (!\is_object($value) || !method_exists($value, '__toString'))) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $value = (string) $value;

        if ($value === '') {
            return;
        }

        $patterns = $this->badPhrases->toPatternCollection();

        if ($this->matcher->matches($value, $patterns)) {
            $this->context->buildViolation($constraint->message)
                ->setCode(NoBadPhrases::CONTAINS_BAD_PHRASE_ERROR)
                ->addViolation();
        }
    }
}
