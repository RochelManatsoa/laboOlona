<?php

namespace App\Form\Facebook;

use App\Entity\Facebook\Contest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ContestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => false,
                'label' => 'Nom du concours',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Nom du concours.',
            ])
            ->add('status', ChoiceType::class, [
                'choices' => Contest::getStatuses(),
                'required' => false,
                'label' => 'Status',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Status du concours.',
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Description',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Notes pour la réservation. Ces notes seront visibles par les utilisateurs qui ont accès à la réservation.',
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ]
            ])
            ->add('startDate', DateTimeType::class, [
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'placeholder' => [
                    'year' => 'Année', 'month' => 'Mois', 'day' => 'Jour', 'hour' => 'Heure', 'minute' => 'Minute'
                ],
                'required' => false,
                'attr' => ['class' => 'date-picker'],
                'label' => 'Date du concours (*)',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Date et heure du concours.',
            ])
            ->add('endDate', DateTimeType::class, [
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'placeholder' => [
                    'year' => 'Année', 'month' => 'Mois', 'day' => 'Jour', 'hour' => 'Heure', 'minute' => 'Minute'
                ],
                'required' => false,
                'attr' => ['class' => 'date-picker'],
                'label' => 'Date fin du concours (*)',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Date et heure de la fin du concours.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contest::class,
        ]);
    }
}
