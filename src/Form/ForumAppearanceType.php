<?php

namespace App\Form;

use App\Form\Model\ForumData;
use App\Form\Type\ThemeSelectorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ForumAppearanceType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('suggestedTheme', ThemeSelectorType::class, [
                'label' => 'label.suggested_theme',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => ForumData::class,
            'validation_groups' => ['appearance'],
        ]);
    }
}
