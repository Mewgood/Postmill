<?php

namespace App\Form;

use App\Form\Model\UserContributionsPage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UserContributionsPageType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('timestamp', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'allow_extra_fields' => true,
            'csrf_protection' => false,
            'data_class' => UserContributionsPage::class,
            'method' => 'GET',
        ]);
    }
}
