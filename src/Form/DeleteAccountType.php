<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\EqualTo;
use Symfony\Component\Validator\Constraints\NotBlank;

final class DeleteAccountType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('username', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                    new EqualTo($options['username']),
                ],
                'label' => 'label.confirm_username',
            ])
            ->add('confirm', CheckboxType::class, [
                'label' => 'label.delete_account_confirmation',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(['username' => null]);
        $resolver->setAllowedTypes('username', ['string']);
        $resolver->setRequired('username');
    }
}
