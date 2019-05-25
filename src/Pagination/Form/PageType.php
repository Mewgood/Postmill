<?php

namespace App\Pagination\Form;

use App\Pagination\PageInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PageType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        /** @var PageInterface $data */
        $data = $builder->getData();

        foreach ($data->getPaginationFields() as $field) {
            $builder->add($field, HiddenType::class);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'allow_extra_fields' => true,
            'csrf_protection' => false,
            'data_class' => PageInterface::class,
            'method' => 'GET',
            'validation_groups' => [],
        ]);
    }
}
