<?php

namespace App\Form;

use App\Entity\BadPhrase;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class BadPhraseType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder
            ->add('phrase', TextType::class, [
                'label' => 'bad_phrase.phrase_to_ban',
            ])
            ->add('phraseType', ChoiceType::class, [
                'choices' => [
                    'bad_phrase.type_text' => BadPhrase::TYPE_TEXT,
                    'bad_phrase.type_regex' => BadPhrase::TYPE_REGEX,
                ],
                'label' => 'label.type',
            ]);
    }
}
