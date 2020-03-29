<?php

namespace App\Validator\Constraints;

use App\Entity\BadPhrase;
use App\Repository\BadPhraseRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class NoBadPhrasesValidator extends ConstraintValidator {
    /**
     * @var BadPhraseRepository
     */
    private $badPhrases;

    public function __construct(BadPhraseRepository $badPhrases) {
        $this->badPhrases = $badPhrases;
    }

    public function validate($value, Constraint $constraint): void {
        if ($value === null) {
            return;
        }

        if (!$constraint instanceof NoBadPhrases) {
            throw new UnexpectedTypeException($constraint, NoBadPhrases::class);
        }

        if (!\is_scalar($value) && (!\is_object($value) || !method_exists($value, '__toString'))) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $value = (string) $value;

        if ($value === '') {
            return;
        }

        foreach ($this->buildMatchingRegexes() as $regex) {
            if (preg_match($regex, $value)) {
                $this->context->buildViolation($constraint->message)
                    ->setCode(NoBadPhrases::CONTAINS_BAD_PHRASE_ERROR)
                    ->addViolation();
                break;
            }
        }
    }

    /**
     * @return string[]
     * @todo caching
     */
    private function buildMatchingRegexes(): array {
        $regexes = [];
        $regex = '@';
        $total = 1;

        foreach ($this->badPhrases->findAll() as $entry) {
            \assert($entry instanceof BadPhrase);

            switch ($entry->getPhraseType()) {
            case BadPhrase::TYPE_TEXT:
                $part = '(?:(?i)\b'.preg_quote($entry->getPhrase(), '@').'\b)';
                break;
            case BadPhrase::TYPE_REGEX:
                $part = '(?:'.addcslashes($entry->getPhrase(), '@').')';
                break;
            default:
                throw new \DomainException('Unknown phrase type');
            }

            if ($total > 1) {
                $part = "|$part";
            }

            $len = strlen($part);

            if ($total + $len + 2 > 32766) {
                $regex .= '@u';
                $regexes[] = $regex;
                $regex = '@';
                $total = 1;
            }

            $regex .= $part;
            $total += $len;
        }

        if ($total > 1) {
            $regex .= '@u';
            $regexes[] = $regex;
        }

        return $regexes;
    }
}
