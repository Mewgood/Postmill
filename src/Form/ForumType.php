<?php

namespace App\Form;

use App\DataObject\ForumData;
use App\Form\Type\HoneypotType;
use App\Form\Type\MarkdownType;
use App\Form\Type\ForumTagsType;
use App\Repository\ForumRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class ForumType extends AbstractType {
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var ForumRepository
     */
    private $forums;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        ForumRepository $forums
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->forums = $forums;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void {
        if ($options['honeypot']) {
            $builder->add('email', HoneypotType::class);
        }

        $builder
            ->add('name', TextType::class, [
                'label' => 'label.name',
            ])
            ->add('title', TextType::class, [
                'label' => 'label.title',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'label.description',
                'help' => 'help.forum_description',
            ])
            ->add('sidebar', MarkdownType::class, [
                'label' => 'label.sidebar',
            ])
            ->add('tags', ForumTagsType::class, [
                'label' => 'label.tags',
                'required' => false,
            ])
        ;

        $forumId = $builder->getData() ? $builder->getData()->getId() : null;
        $forum = $forumId !== null ? $this->forums->find($forumId) : null;

        if ($forum && $this->authorizationChecker->isGranted('set_log_visibility', $forum)) {
            $builder->add('moderationLogPublic', CheckboxType::class, [
                'label' => 'forum_form.moderation_log_public',
                'required' => false,
            ]);
        }

        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            $builder->add('featured', CheckboxType::class, [
                'label' => 'forum_form.featured',
                'required' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => ForumData::class,
            'honeypot' => true,
            'validation_groups' => static function (FormInterface $form) {
                $editing = $form->getData() && $form->getData()->getId();

                return $editing ? ['update_forum'] : ['create_forum'];
            },
        ]);

        $resolver->setAllowedTypes('honeypot', ['bool']);
    }
}
