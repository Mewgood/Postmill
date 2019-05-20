<?php

namespace App\Form;

use App\Entity\Submission;
use App\Form\Model\SubmissionPage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SubmissionPageType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
        foreach (Submission::SORT_FIELD_MAP[$options['sort_by']] as $field) {
            $builder->add($field, HiddenType::class);
        }
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'allow_extra_fields' => true,
            'csrf_protection' => false,
            'data_class' => SubmissionPage::class,
            'method' => 'GET',
            'sort_by' => null,
            'validation_groups' => function (FormInterface $form) {
                $sortBy = $form->getConfig()->getOption('sort_by');

                return ['all', $sortBy];
            },
        ]);
        $resolver->setAllowedValues('sort_by', Submission::SORT_OPTIONS);
        $resolver->setRequired(['sort_by']);
    }
}
