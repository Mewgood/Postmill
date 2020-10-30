<?php

namespace App\Form;

use App\DataObject\BlocklistData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BlocklistType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder
            ->add('name', TextType::class, [
                'label' => 'label.name',
            ])
            ->add('url', UrlType::class, [
                'label' => 'label.url',
            ])
            ->add('regex', TextType::class, [
                'label' => 'label.regex',
            ])
            ->add('ttl', IntegerType::class, [
                'data' => 0,
                'label' => 'label.time_to_live',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => BlocklistData::class,
        ]);
    }
}
