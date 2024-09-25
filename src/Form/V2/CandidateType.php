<?php

namespace App\Form\V2;

use App\Entity\User;
use App\Entity\Secteur;
use App\Entity\Availability;
use App\Entity\Candidate\Social;
use App\Entity\CandidateProfile;
use App\Entity\BusinessModel\Boost;
use App\Entity\Candidate\Competences;
use App\Entity\Candidate\TarifCandidat;
use App\Form\Candidat\TarifCandidatType;
use Symfony\Component\Form\AbstractType;
use App\Entity\BusinessModel\BoostVisibility;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;

class CandidateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('resume', TextareaType::class, [
                'required' => false ,
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ]
            ])
            ->add('localisation', CountryType::class, [
                'required' => false,
                'label' => 'Pays de résidence (obligatoire)',
                'placeholder' => 'Sélectionner votre pays', 
            ])
            ->add('titre')
            ->add('secteurs', EntityType::class, [
                'required' => false,
                'class' => Secteur::class,
                'label' => 'Secteur d\'expertise *',
                'choice_label' => 'nom',
                'autocomplete' => true,
                'multiple' => true,
            ])
            ->add('tarifCandidat', TarifCandidatType::class, [
                'required' => false,
                'label' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CandidateProfile::class,
        ]);
    }
}
