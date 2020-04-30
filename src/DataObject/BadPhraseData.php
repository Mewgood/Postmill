<?php

namespace App\DataObject;

use App\Entity\BadPhrase;
use App\Validator\Unique;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @Unique({"phrase", "phraseType"}, entityClass="App\Entity\BadPhrase", idFields={"id"}, errorPath="phrase")
 */
class BadPhraseData {
    /**
     * @var UuidInterface|null
     */
    private $id;

    /**
     * @Assert\Length(max=150)
     * @Assert\NotBlank()
     *
     * @var string|null
     */
    private $phrase;

    /**
     * @Assert\Choice(BadPhrase::TYPES)
     * @Assert\NotBlank()
     *
     * @var string|null
     */
    private $phraseType;

    public function getId(): ?UuidInterface {
        return $this->id;
    }

    public function toBadPhrase(): BadPhrase {
        return new BadPhrase($this->phrase, $this->phraseType);
    }

    public function getPhrase(): ?string {
        return $this->phrase;
    }

    public function setPhrase(?string $phrase): void {
        $this->phrase = $phrase;
    }

    public function getPhraseType(): ?string {
        return $this->phraseType;
    }

    public function setPhraseType(?string $phraseType): void {
        $this->phraseType = $phraseType;
    }

    /**
     * @Assert\Callback()
     */
    public function validateRegexPhrase(ExecutionContextInterface $context): void {
        if ($this->phraseType === BadPhrase::TYPE_REGEX && $this->phrase !== null) {
            $return = @preg_match('@'.addcslashes($this->phrase, '@').'@u', '');

            if ($return === 1) {
                $context->buildViolation('bad_phrase.must_not_match_empty')
                    ->atPath('phrase')
                    ->addViolation();
            } elseif ($return !== 0) {
                $context->buildViolation('bad_phrase.invalid_regex')
                    ->atPath('phrase')
                    ->addViolation();
            }
        }
    }
}
