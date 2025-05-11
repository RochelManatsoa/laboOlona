<?php

namespace App\Form\BusinessModel;

use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use Symfony\Component\Form\AbstractType;
use App\Entity\BusinessModel\Subcription;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Form\Autocomplete\CandidateAutocompleteField;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class SubcriptionForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('duration', null, [
                'label' => 'Durée',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Durée de la souscription.',
            ])
            ->add('type', ChoiceType::class, [
                'choices' => Subcription::getTypes(),
                'label' => 'Compte',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Type de compte.',
            ])
            ->add('active')
            ->add('startDate', DateType::class, [
                'label' => 'Date du début',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Type de compte.',
                'required' => false,
                'attr' => ['class' => 'date-picker'],
                'data' => new \DateTime(),
            ])
            ->add('endDate', DateType::class, [
                'label' => 'Date de fin',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Type de compte.',
                'required' => false,
                'attr' => ['class' => 'date-picker'],
                'data' => new \DateTime(),
            ])
            ->add('entreprise', EntityType::class, [
                'class' => EntrepriseProfile::class,
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'choice_label' => function(?EntrepriseProfile $entrepriseProfile) {
                    return $entrepriseProfile ? $entrepriseProfile->getNom() : '';
                },
                'placeholder' => 'Choisir une entreprise', 
                'required' => false,
                'autocomplete' => true
            ])
            ->add('candidat', CandidateAutocompleteField::class, [
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'label' => 'Candidat',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Subcription::class,
        ]);
    }
}
