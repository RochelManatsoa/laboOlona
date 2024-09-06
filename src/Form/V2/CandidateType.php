<?php

namespace App\Form\V2;

use App\Entity\Availability;
use App\Entity\BusinessModel\Boost;
use App\Entity\BusinessModel\BoostVisibility;
use App\Entity\Candidate\Competences;
use App\Entity\Candidate\Social;
use App\Entity\Candidate\TarifCandidat;
use App\Entity\CandidateProfile;
use App\Entity\Secteur;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CandidateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('resume')
            ->add('localisation')
            ->add('birthday')
            ->add('titre')
            ->add('competences', EntityType::class, [
                'class' => Competences::class,
                'choice_label' => 'nom',
                'multiple' => true,
            ])
            ->add('secteurs', EntityType::class, [
                'class' => Secteur::class,
                'label' => 'Secteur d\'expertise *',
                'choice_label' => 'nom',
                'autocomplete' => true,
                'multiple' => true,
                'required' => true,
            ])
            ->add('availability', EntityType::class, [
                'class' => Availability::class,
                'choice_label' => 'id',
            ])
            ->add('tarifCandidat', EntityType::class, [
                'class' => TarifCandidat::class,
                'choice_label' => 'id',
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
