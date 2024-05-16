<?php

namespace App\Form\Moderateur\Profile;

use App\Entity\Secteur;
use App\Entity\CandidateProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CandidatType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('resume')
            ->add('fileName')
            ->add('localisation')
            ->add('birthday')
            ->add('titre')
            ->add('cv')
            ->add('status')
            ->add('emailSent')
            ->add('competences')
            ->add('secteurs', EntityType::class, [
                'class' => Secteur::class,
                'label' => 'Secteur d\'expertise *',
                'choice_label' => 'nom',
                'autocomplete' => true,
                'multiple' => true,
                'required' => true,
            ])
            // ->add('social')
            // ->add('availability')
            ->add('tarifCandidat')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CandidateProfile::class,
        ]);
    }
}
