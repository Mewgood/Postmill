<?php

namespace App\Form;

use App\DataObject\SubmissionData;
use App\Entity\Forum;
use App\Entity\Submission;
use App\Form\EventListener\SubmissionImageListener;
use App\Form\Type\HoneypotType;
use App\Form\Type\MarkdownType;
use App\Form\Type\UserFlagType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class SubmissionType extends AbstractType {
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var SubmissionImageListener
     */
    private $submissionImageListener;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        SubmissionImageListener $submissionImageListener
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->submissionImageListener = $submissionImageListener;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void {
        if ($options['honeypot']) {
            $builder->add('email', HoneypotType::class);
        }

        /** @var SubmissionData $data */
        $data = $builder->getData();
        $editing = $data->getId() !== null;

        $builder
            ->add('title', TextareaType::class, [
                'max_chars' => Submission::MAX_TITLE_LENGTH,
            ])

            ->add('body', MarkdownType::class, [
                'max_chars' => Submission::MAX_BODY_LENGTH,
                'required' => false,
            ]);

        if (!$editing || $data->getMediaType() === Submission::MEDIA_URL) {
            $builder->add('url', UrlType::class, [
                // TODO: indicate that this check must be 8-bit
                'max_chars' => Submission::MAX_URL_LENGTH,
                'required' => false,
            ]);
        }

        if (!$editing && $this->authorizationChecker->isGranted('upload_image')) {
            $builder
                ->add('mediaType', ChoiceType::class, [
                    'choices' => [
                        'submission_form.url' => Submission::MEDIA_URL,
                        'label.image' => Submission::MEDIA_IMAGE,
                    ],
                    'choice_name' => function ($key) {
                        return $key;
                    },
                    'data' => Submission::MEDIA_URL,
                    'expanded' => true,
                    'label' => 'label.media_type',
                ])
                ->add('image', FileType::class, [
                    'label' => 'label.upload_image',
                    'property_path' => 'uploadedImage',
                    'required' => false,
                ]);

            $builder->addEventSubscriber($this->submissionImageListener);
        }

        if (!$editing) {
            $builder->add('forum', EntityType::class, [
                'class' => Forum::class,
                'choice_label' => 'name',
                'query_builder' => function (EntityRepository $repository) {
                    return $repository->createQueryBuilder('f')
                        ->orderBy('f.name', 'ASC');
                },
                'placeholder' => 'placeholder.choose_one',
                'required' => false, // enable a blank choice
            ]);
        }

        $builder->add('userFlag', UserFlagType::class, [
            'forum' => $options['forum'] ?? null,
        ]);

        $builder->add('submit', SubmitType::class, [
            'label' => 'submission_form.'.($editing ? 'edit' : 'create'),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => SubmissionData::class,
            'label_format' => 'submission_form.%name%',
            'honeypot' => true,
            'validation_groups' => function (FormInterface $form) {
                return $this->getValidationGroups($form);
            },
        ]);

        $resolver->setAllowedTypes('honeypot', ['bool']);
    }

    private function getValidationGroups(FormInterface $form): array {
        $groups = ['Default'];
        $trusted = $this->authorizationChecker->isGranted('ROLE_TRUSTED_USER');
        $editing = $form->getData() && $form->getData()->getId();

        if (!$editing) {
            $groups[] = 'create';

            if (!$trusted) {
                $groups[] = 'untrusted_user_create';
            }

            if ($form->has('mediaType')) {
                $groups[] = 'media';

                $mediaType = $form->get('mediaType')->getData();

                if (\in_array($mediaType, Submission::MEDIA_TYPES, true)) {
                    $groups[] = $mediaType;
                }
            }
        } else {
            $groups[] = 'edit';

            if ($form->getData()->getMediaType() === Submission::MEDIA_URL) {
                $groups[] = 'url';
            }

            if (!$trusted) {
                $groups[] = 'untrusted_user_edit';
            }
        }

        return $groups;
    }
}
