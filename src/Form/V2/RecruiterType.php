<?php

namespace App\Form\V2;

use App\Entity\User;
use App\Entity\Secteur;
use App\Entity\Finance\Devise;
use App\Entity\EntrepriseProfile;
use App\Entity\BusinessModel\Boost;
use Symfony\Component\Form\AbstractType;
use App\Entity\BusinessModel\BoostVisibility;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class RecruiterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('taille', ChoiceType::class, [
                'choices' => EntrepriseProfile::CHOICE_SIZE,
            ])
            ->add('localisation', CountryType::class, [
                'required' => false,
                'label' => 'Pays de résidence (obligatoire)',
            ])
            ->add('siteWeb', null, ['required' => false])
            ->add('description', TextareaType::class, [
                'required' => false, 
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ]
            ])
            ->add('nom', null, ['required' => false])
            ->add('secteurs', EntityType::class, [
                'required' => false,
                'class' => Secteur::class,
                'label' => 'Secteur d\'expertise *',
                'choice_label' => 'nom',
                'autocomplete' => true,
                'multiple' => true,
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
