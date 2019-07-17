<?php

namespace App\Form;

use App\Form\Model\SiteData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SiteSettingsType extends AbstractType {
    private const ROLES = [
        'label.admins' => 'ROLE_ADMIN',
        'label.trusted_users' => 'ROLE_TRUSTED_USER',
        'label.everyone' => 'ROLE_USER',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('siteName', TextType::class, [
                'label' => 'site_settings.site_name',
            ])
            ->add('wikiEnabled', CheckboxType::class, [
                'label' => 'site_settings.wiki_enabled',
                'required' => false,
            ])
            ->add('forumCreateRole', ChoiceType::class, [
                'choices' => self::ROLES,
                'label' => 'site_settings.forum_create_role',
            ])
            ->add('imageUploadRole', ChoiceType::class, [
                'choices' => self::ROLES,
                'label' => 'site_settings.image_upload_role',
            ])
            ->add('wikiEditRole', ChoiceType::class, [
                'choices' => self::ROLES,
                'label' => 'site_settings.wiki_edit_role',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => SiteData::class,
        ]);
    }
}
