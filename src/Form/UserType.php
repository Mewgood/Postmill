<?php

namespace App\Form;

use App\DataObject\UserData;
use App\Form\EventListener\PasswordEncodingSubscriber;
use App\Form\Type\HoneypotType;
use Gregwar\CaptchaBundle\Type\CaptchaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

final class UserType extends AbstractType {
    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder) {
        $this->encoder = $encoder;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void {
        if ($options['honeypot']) {
            $builder->add('phone', HoneypotType::class);
        }

        $editing = $builder->getData() && $builder->getData()->getId();

        $builder
            ->add('username', TextType::class, [
                'help' => 'user.username_rules',
            ])
            ->add('password', RepeatedType::class, [
                'help' => 'user.password_rules',
                'property_path' => 'plainPassword',
                'required' => !$editing,
                'first_options' => ['label' => $editing ? 'user_form.new_password' : 'user_form.password'],
                'second_options' => ['label' => $editing ? 'user_form.repeat_new_password' : 'user_form.repeat_password'],
                'type' => PasswordType::class,
            ])
            ->add('email', EmailType::class, [
                'help' => 'user.email_optional',
                'required' => false,
            ]);

        if (!$editing) {
            $builder->add('verification', CaptchaType::class, [
                'as_url' => true,
                'reload' => true,
            ]);
        }

        $builder->addEventSubscriber(new PasswordEncodingSubscriber($this->encoder));
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void {
        if ($form->getData() && $form->getData()->getId()) {
            // don't auto-complete the password fields when editing the user
            $view['password']['first']->vars['attr']['autocomplete'] = 'new-password';
            $view['password']['second']->vars['attr']['autocomplete'] = 'new-password';
        }
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => UserData::class,
            'honeypot' => true,
            'label_format' => 'user_form.%name%',
            'validation_groups' => function (FormInterface $form) {
                if ($form->getData()->getId() !== null) {
                    $groups[] = 'edit';
                } else {
                    $groups[] = 'registration';
                }

                return $groups;
            },
        ]);

        $resolver->setAllowedTypes('honeypot', ['bool']);
    }
}
