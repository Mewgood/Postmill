<?php

namespace App\Form;

use App\Entity\Comment;
use App\Entity\Forum;
use App\Form\Model\CommentData;
use App\Form\Type\HoneypotType;
use App\Form\Type\MarkdownType;
use App\Form\Type\UserFlagType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CommentType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
        if ($options['honeypot']) {
            $builder->add('email', HoneypotType::class);
        }

        $builder->add('comment', MarkdownType::class, [
            'max_chars' => Comment::MAX_BODY_LENGTH,
            'property_path' => 'body',
        ]);

        $builder->add('userFlag', UserFlagType::class, [
            'forum' => $options['forum'],
        ]);

        $editing = $builder->getData() && $builder->getData()->getEntityId();

        $builder->add('submit', SubmitType::class, [
            'label' => $editing ? 'action.save' : 'action.post',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => CommentData::class,
            'forum' => null, // for UserFlagTrait
            'honeypot' => true,
            'label_format' => 'comment_form.%name%',
            'validation_groups' => function (FormInterface $form) {
                $groups = ['Default'];

                if ($form->getData() && $form->getData()->getEntityId()) {
                    $groups[] = 'edit';
                } else {
                    $groups[] = 'create';
                }

                return $groups;
            },
        ]);

        $resolver->setAllowedTypes('forum', ['null', Forum::class]); // ditto
        $resolver->setAllowedTypes('honeypot', ['bool']);
    }
}
