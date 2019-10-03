<?php

namespace App\Form;

use App\Entity\ForumCategory;
use App\DataObject\ForumData;
use App\Form\Type\HoneypotType;
use App\Form\Type\MarkdownType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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

    public function __construct(AuthorizationCheckerInterface $authorizationChecker) {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void {
        if ($options['honeypot']) {
            $builder->add('email', HoneypotType::class);
        }

        $builder
            ->add('name', TextType::class)
            ->add('title', TextType::class)
            ->add('description', TextareaType::class, [
                'help' => 'help.forum_description',
            ])
            ->add('sidebar', MarkdownType::class, [
                'label' => 'label.sidebar',
            ])
            ->add('category', EntityType::class, [
                'class' => ForumCategory::class,
                'choice_label' => 'name',
                'query_builder' => function (EntityRepository $repository) {
                    return $repository->createQueryBuilder('fc')
                        ->orderBy('fc.name', 'ASC');
                },
                'required' => false,
                'placeholder' => 'forum_form.uncategorized_placeholder',
            ])
        ;

        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            $builder->add('featured', CheckboxType::class, [
                'required' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => ForumData::class,
            'label_format' => 'forum_form.%name%',
            'honeypot' => true,
            'validation_groups' => function (FormInterface $form) {
                $editing = $form->getData() && $form->getData()->getId();

                return $editing ? ['update'] : ['create'];
            },
        ]);

        $resolver->setAllowedTypes('honeypot', ['bool']);
    }
}
