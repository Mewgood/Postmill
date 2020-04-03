<?php

namespace App\Form;

use App\DataObject\ForumData;
use App\Entity\ForumCategory;
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
            ->add('category', EntityType::class, [
                'class' => ForumCategory::class,
                'choice_label' => 'name',
                'label' => 'label.category',
                'query_builder' => static function (EntityRepository $repository) {
                    return $repository->createQueryBuilder('fc')
                        ->orderBy('fc.name', 'ASC');
                },
                'required' => false,
                'placeholder' => 'placeholder.uncategorized',
            ])
        ;

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

                return $editing ? ['update'] : ['create'];
            },
        ]);

        $resolver->setAllowedTypes('honeypot', ['bool']);
    }
}
