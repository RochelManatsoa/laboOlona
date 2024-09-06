<?php

namespace App\Form\V2;

use App\Entity\BusinessModel\Boost;
use App\Entity\BusinessModel\BoostVisibility;
use App\Entity\EntrepriseProfile;
use App\Entity\Finance\Devise;
use App\Entity\Secteur;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecruiterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('taille')
            ->add('localisation')
            ->add('siteWeb')
            ->add('description')
            ->add('nom')
            ->add('secteurs', EntityType::class, [
                'class' => Secteur::class,
                'label' => 'Secteur d\'expertise *',
                'choice_label' => 'nom',
                'autocomplete' => true,
                'multiple' => true,
                'required' => true,
            ])
            ->add('devise', EntityType::class, [
                'class' => Devise::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EntrepriseProfile::class,
        ]);
    }
}
