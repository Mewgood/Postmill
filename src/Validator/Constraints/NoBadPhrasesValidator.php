<?php

namespace App\Validator\Constraints;

use App\Entity\BadPhrase;
use App\Repository\BadPhraseRepository;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class NoBadPhrasesValidator extends ConstraintValidator {
    /**
     * @var BadPhraseRepository
     */
    private $badPhrases;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(BadPhraseRepository $badPhrases, ?LoggerInterface $logger) {
        $this->badPhrases = $badPhrases;
        $this->logger = $logger ?? new NullLogger();
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
            $success = @preg_match($regex, $value);

            if ($success) {
                $this->context->buildViolation($constraint->message)
                    ->setCode(NoBadPhrases::CONTAINS_BAD_PHRASE_ERROR)
                    ->addViolation();
                break;
            }

            if ($success === false) {
                $this->logger->error('Regex matching failed', [
                    'error' => preg_last_error_msg(),
                    'pattern' => $regex,
                    'subject' => $value,
                ]);
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
                $part = '(?:'.addcslashes($entry->getPhrase(), '@');
                if (preg_match('@\(\?[A-Za-z]*?x[A-Za-z]*\).*[^\\\\]#@', $part)) {
                    // handle (?x) with comment
                    $part .= "\n";
                }
                $part .= ')';
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

        $this->logger->debug('"Bad phrase" regex(es) built', [
            'regexes' => $regexes,
        ]);

        return $regexes;
    }
}
