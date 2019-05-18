<?php

namespace App\Form;

use App\Entity\Submission;
use App\Form\Model\UserData;
use App\Form\Type\ThemeSelectorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UserSettingsType extends AbstractType {
    /**
     * @var array
     */
    private $availableLocales;

    public function __construct(array $availableLocales) {
        $this->availableLocales = $availableLocales;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('locale', ChoiceType::class, [
                'choices' => $this->buildLocaleChoices(),
                'choice_translation_domain' => false,
            ])
            ->add('timezone', TimezoneType::class, [
                'input' => 'datetimezone',
                'label' => 'label.timezone',
            ])
            ->add(
                $builder->create('frontPage', FormType::class, [
                    'error_bubbling' => false,
                    'label' => 'label.front_page',
                    'inherit_data' => true
                ])
                ->add('filterBy', ChoiceType::class, [
                    'choices' => [
                        'label.featured' => Submission::FRONT_FEATURED,
                        'label.subscribed' => Submission::FRONT_SUBSCRIBED,
                        'label.all' => Submission::FRONT_ALL,
                        'label.moderated' => Submission::FRONT_MODERATED,
                    ],
                    'error_bubbling' => true,
                    'label' => 'label.filter_by',
                    'property_path' => 'frontPage',
                ])
                ->add('sortBy', ChoiceType::class, [
                    'choices' => [
                        'submissions.sort_by_hot' => Submission::SORT_HOT,
                        'submissions.sort_by_new' => Submission::SORT_NEW,
                        'submissions.sort_by_active' => Submission::SORT_ACTIVE,
                    ],
                    'error_bubbling' => true,
                    'label' => 'label.sort_by',
                    'property_path' => 'frontPageSortMode',
                ])
            )
            ->add('openExternalLinksInNewTab', CheckboxType::class, [
                'required' => false,
                'label' => 'label.open_external_links_in_new_tab',
            ])
            ->add('autoFetchSubmissionTitles', CheckboxType::class, [
                'label' => 'label.auto_fetch_submission_titles',
                'required' => false,
            ])
            ->add('enablePostPreviews', CheckboxType::class, [
                'label' => 'label.show_post_previews',
                'required' => false,
            ])
            ->add('showThumbnails', CheckboxType::class, [
                'label' => 'label.show_thumbnails',
                'required' => false,
            ])
            ->add('notifyOnReply', CheckboxType::class, [
                'help' => 'help.notify_on_reply',
                'label' => 'label.notify_on_reply',
                'required' => false,
            ])
            ->add('notifyOnMentions', CheckboxType::class, [
                'help' => 'help.notify_on_mentions',
                'label' => 'label.notify_on_mentions',
                'required' => false,
            ])
            ->add('allowPrivateMessages', CheckboxType::class, [
                'help' => 'help.allow_private_messages',
                'label' => 'label.allow_private_messages',
                'required' => false,
            ])
            ->add('preferredFonts', TextType::class, [
                'required' => false,
            ])
            ->add('preferredTheme', ThemeSelectorType::class, [
                'label' => 'label.preferred_theme',
                'required' => false,
            ])
            ->add('showCustomStylesheets', CheckboxType::class, [
                'label' => 'label.let_forums_override_preferred_theme',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => UserData::class,
            'label_format' => 'user_settings_form.%name%',
            'validation_groups' => ['settings'],
        ]);
    }

    private function buildLocaleChoices(): array {
        $localeChoices = [];
        $localeBundle = Intl::getLocaleBundle();

        foreach ($this->availableLocales as $locale) {
            $name = $localeBundle->getLocaleName($locale, $locale);

            $localeChoices[$name] = $locale;
        }

        \uksort($localeChoices, function ($a, $b) {
            [$a, $b] = \array_map(function ($key) {
                return \transliterator_transliterate(
                    'NFKD; Latin; Latin/US-ASCII; [:Nonspacing Mark:] Remove; Lower',
                    $key
                );
            }, [$a, $b]);

            return \strnatcasecmp($a, $b);
        });

        return $localeChoices;
    }
}
