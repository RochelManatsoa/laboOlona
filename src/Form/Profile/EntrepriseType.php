<?php

namespace App\Form\Profile;

use App\Entity\Secteur;
use App\Form\ContactType;
use App\Entity\EntrepriseProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class EntrepriseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de votre entreprise'
            ])
            ->add('taille', ChoiceType::class, [
                'choices' => EntrepriseProfile::CHOICE_SIZE,
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'rows' => 6
                ]
            ])
            ->add('secteurs', EntityType::class, [
                    'class' => Secteur::class,
                    'choice_label' => 'nom',
                    'label' => 'app_identity_company.sector_multiple',
                    'autocomplete' => true,
                    'multiple' => true,
            ])
            ->add('siteWeb', TextType::class, [
                'label' => 'Site Web'
            ])
            ->add('entreprise', ContactType::class, [
                'label' => false
            ])
            ->add('localisation', CountryType::class, [
                'label' => 'Pays'
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
