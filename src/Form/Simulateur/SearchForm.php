<?php

namespace App\Form\Simulateur;

use App\Data\Finance\SearchData;
use App\Entity\Finance\Contrat;
use App\Entity\Finance\Simulateur;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('q', TextType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => 'Nom ou prénom'
                ]
            ])
            ->add('salaires', ChoiceType::class, [
                'choices' => [
                    '+ de 4 M Ar' => 'more4' ,
                    'Entre 4 et 3 M Ar' => 'bet4and3' ,
                    'Entre 3 et 2 M Ar' => 'bet3and2' ,
                    'Entre 2 et 1 M Ar' => 'bet2and1' ,
                    '- de 1 M Ar' => 'less1' ,
                ],
                'label' => false,
                'required' => false,
                'placeholder' => 'Salaire',
                'attr' => [
                ]
            ])
            ->add('status', ChoiceType::class, [
                'choices' => Simulateur::getStatuses(),
                'label' => false,
                'required' => false,
                'placeholder' => 'Status',
                'attr' => [
                ]
            ])
            ->add('statusDemande', ChoiceType::class, [
                'choices' => Contrat::getStatuses(),
                'label' => false,
                'required' => false,
                'placeholder' => 'Status',
                'attr' => [
                ]
            ])
            ->add('type', ChoiceType::class, [
                'choices' => User::getChoices(),
                'label' => false,
                'required' => false,
                'placeholder' => 'Type de compte',
                'attr' => [
                ]
            ])
        ;
    }
    
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchData::class,
            'method' => 'GET',
            'csrf_protection' => false
        ]);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return '';
    }
}