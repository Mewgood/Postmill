<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MarkdownType extends AbstractType {
    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'trim' => false,
        ]);
    }

    public function getParent(): string {
        return TextareaType::class;
    }
}
