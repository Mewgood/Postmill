<?php

namespace App\Form;

use App\Form\Model\SiteData;
use App\Form\Type\ThemeSelectorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SiteSettingsType extends AbstractType {
    private const ROLES = [
        'role.admins' => 'ROLE_ADMIN',
        'role.whitelisted' => 'ROLE_WHITELISTED',
        'role.everyone' => 'ROLE_USER',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder
            ->add('siteName', TextType::class, [
                'label' => 'site_settings.site_name',
            ])
            ->add('defaultTheme', ThemeSelectorType::class, [
                'help' => 'site_settings.site_theme_help',
                'label' => 'site_settings.site_theme',
                'required' => false,
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

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => SiteData::class,
        ]);
    }
}
