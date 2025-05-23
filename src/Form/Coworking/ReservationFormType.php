<?php

namespace App\Form\Coworking;

use App\Entity\Coworking\Reservation;
use Symfony\Component\Form\AbstractType;
use Karser\Recaptcha3Bundle\Form\Recaptcha3Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class ReservationFormType extends AbstractType
{    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'Nom complet'
                ]
            ])
            ->add('guest', ChoiceType::class, [
                'choices' => [
                    '1 personne' => '1',
                    '2 personnes' => '2',
                    '3 personnes' => '3',
                    '4 personnes' => '4',
                    '5 personnes' => '5',
                ],
                'label' => false,
                'attr' => [
                    'placeholder' => '2 personnes'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'Email'
                ]
            ])
            ->add('phone', TextType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'Téléphone'
                ]
            ])
            ->add('date', DateTimeType::class, [
                'label' => false,
                'required' => false,
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'data' => new \DateTime(),
            ])
            ->add('private', CheckboxType::class, [
                'mapped' => false,
                'label' => 'Je souhaite privatiser',
                'required' => false,
                'row_attr' => ['class' => 'd-flex align-items-center'], // Flexbox pour aligner
                'label_attr' => ['class' => 'ms-2 text-white'], 
            ])
            ->add('captcha', Recaptcha3Type::class, [
                'constraints' => new Recaptcha3(),
                'action_name' => 'reservation',
                'locale' => 'fr',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}
