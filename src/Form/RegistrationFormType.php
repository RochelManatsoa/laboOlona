<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => false,
                'row_attr' => ['class' => 'col-md-12 mb-3'],
                'attr' => ['class' => 'form-control', 'placeholder' => 'app_register.email']
            ])
            ->add('nom', null, [
                'label' => false,
                'row_attr' => ['class' => 'col-md-6 mb-3'],
                'attr' => ['class' => 'form-control', 'placeholder' => 'app_register.first_name']
            ])
            ->add('prenom', null, [
                'label' => false,
                'row_attr' => ['class' => 'col-md-6 mb-3'],
                'attr' => ['class' => 'form-control', 'placeholder' => 'app_register.last_name']
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => false,
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'You should agree to our terms.',
                    ]),
                ],
                'attr' => [
                    'label' => 'app_register.agree_terms',
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'invalid_message' => 'The password fields must match.',
                'options' => ['attr' => ['class' => '']],
                'required' => true,
                'first_options'  => [ 'label' => false, 'attr' => ['placeholder' => 'app_register.password']],
                'second_options' => [ 'label' => false, 'attr' => ['placeholder' => 'app_register.repeat_password']],
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'app_register.not_blank',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
