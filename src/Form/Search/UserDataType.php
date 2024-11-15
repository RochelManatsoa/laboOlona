<?php

namespace App\Form\Search;

use App\Manager\ModerateurManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class UserDataType extends AbstractType
{

    public function __construct(
        private ModerateurManager $moderateurManager,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('days', ChoiceType::class, [
                'choices' => [
                    'Aujourd\'hui' => 1,
                    'Hier' => 2,
                    'Ces 7 derniers jours' => 7,
                    'Ces 14 derniers jours' => 14,
                    'Ces 30 derniers jours' => 30,
                    'Ces 60 derniers jours' => 60,
                    'Ces 90 derniers jours' => 90,
                ],
                'required' => false,
                'label' => 'Nombre de jours à chercher',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
            ])
            ->add('startDate', DateType::class, [
                'required' => false,
                'label' => 'Date de début',
                'widget' => 'single_text',  
                'format' => 'yyyy-MM-dd',   
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Indiquez la date de début de la mission ou de l\'emploi.'
            ])
            ->add('endDate', DateType::class, [
                'required' => false,
                'label' => 'Date de début',
                'widget' => 'single_text',  
                'format' => 'yyyy-MM-dd',   
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Indiquez la date de début de la mission ou de l\'emploi.'
            ])
            ->add('page', HiddenType::class, [
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Filtrer',
                'attr' => [
                    'class' => 'btn btn-dark px-5'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}