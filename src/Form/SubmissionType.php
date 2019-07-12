<?php

namespace App\Form;

use App\Entity\Forum;
use App\Entity\Site;
use App\Entity\Submission;
use App\Form\EventListener\SubmissionImageListener;
use App\Form\Model\SubmissionData;
use App\Form\Type\HoneypotType;
use App\Form\Type\MarkdownType;
use App\Repository\SiteRepository;
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
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class SubmissionType extends AbstractType {
    use UserFlagTrait;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var SiteRepository
     */
    private $siteRepository;

    /**
     * @var SubmissionImageListener
     */
    private $submissionImageListener;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        SiteRepository $siteRepository,
        SubmissionImageListener $submissionImageListener,
        TokenStorageInterface $tokenStorage
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->siteRepository = $siteRepository;
        $this->submissionImageListener = $submissionImageListener;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        if ($options['honeypot']) {
            $builder->add('email', HoneypotType::class);
        }

        /** @var SubmissionData $data */
        $data = $builder->getData();
        $editing = $data->getEntityId() !== null;
        $forum = $data->getForum();

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

        $site = $this->siteRepository->findCurrentSite();

        assert($site instanceof Site);

        if (!$editing && $site->isImageUploadingAllowed()) {
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

        if (
            ($editing && $this->authorizationChecker->isGranted('moderator', $forum)) ||
            $this->authorizationChecker->isGranted('ROLE_ADMIN')
        ) {
            $this->addUserFlagOption($builder, $forum);
        }

        $builder->add('submit', SubmitType::class, [
            'label' => 'submission_form.'.($editing ? 'edit' : 'create'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver) {
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
        $editing = $form->getData() && $form->getData()->getEntityId();

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
