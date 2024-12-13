<?php

namespace App\Form\Coworking;

use Faker\Provider\ar_EG\Text;
use App\Entity\Coworking\Reservation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
                'label' => 'Nom complet'
            ])
            ->add('guest', TextType::class, [
                'label' => 'Nom complet'
            ])
            ->add('email', TextType::class, [
                'label' => 'Nom complet'
            ])
            ->add('phone', TextType::class, [
                'label' => 'Nom complet'
            ])
            ->add('date', DateTimeType::class, [
                'label' => false,
                'required' => false,
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'data' => new \DateTime(),
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Réservé' => Reservation::STATUS_RESERVED,
                    'Confirmé' => Reservation::STATUS_CONFIRMED,
                    'Annulé' => Reservation::STATUS_CANCELLED,
                ],
                'label' => 'Status'
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Note'
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
