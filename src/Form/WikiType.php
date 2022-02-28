<?php

namespace App\Form;

use App\Form\Model\WikiData;
use App\Form\Type\MarkdownType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class WikiType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        if ($options['has_path']) {
            $builder->add('path', TextType::class, [
                'label' => 'label.url',
                'disabled' => !$options['path_mutable'],
            ]);
        }

        $builder
            ->add('title', TextType::class, [
                'label' => 'label.title',
            ])
            ->add('body', MarkdownType::class, [
                'label' => 'label.body',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => WikiData::class,
            'has_path' => false,
            'path_mutable' => false,
            'validation_groups' => function (FormInterface $form) {
                $groups = ['Default'];

                if ($form->has('path')) {
                    $groups[] = 'path';
                }

                return $groups;
            },
        ]);

        $resolver->setAllowedTypes('has_path', 'bool');
        $resolver->setAllowedTypes('path_mutable', 'bool');
    }
}
