<?php

namespace App\Form\BusinessModel;

use App\Entity\BusinessModel\Package;
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
        $subcription = $options['data'] ?? null;

        // Gestion manuelle des dates si invalide ou null
        $start = null;
        $end = null;

        if ($subcription instanceof Subcription) {
            $start = $subcription->getStartDate();
            if (!$start instanceof \DateTimeInterface) {
                $start = new \DateTime();
            }

            $end = $subcription->getEndDate();
            if (!$end instanceof \DateTimeInterface) {
                $end = (clone $start)->modify('+1 month');
            }
        } else {
            $start = new \DateTime();
            $end = (clone $start)->modify('+1 month');
        }
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
                'label_attr' => ['class' => 'fw-bold fs-5'],
                'help' => 'Type de compte.',
                'required' => false,
                'attr' => ['class' => 'date-picker'],
                'data' => $start,
            ])
            ->add('endDate', DateType::class, [
                'label' => 'Date de fin',
                'label_attr' => ['class' => 'fw-bold fs-5'],
                'help' => 'Type de compte.',
                'required' => false,
                'attr' => ['class' => 'date-picker'],
                'data' => $end,
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
            ->add('package', EntityType::class, [
                'class' => Package::class,
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'choice_label' => 'name',
                'placeholder' => 'Séléctionner un package',
                'required' => false,
                'autocomplete' => true
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
