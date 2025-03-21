<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Secteur;
use App\Entity\Finance\Devise;
use App\Entity\EntrepriseProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class EntrepriseProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Raison sociale de l\'entreprise.',
            ])
            ->add('isPremium', CheckboxType::class, [
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'label' => 'Abonnement Premium ?',
                'help' => 'Abonnement Premium',
            ])
            ->add('taille', ChoiceType::class, [
                'choices' => EntrepriseProfile::CHOICE_SIZE,
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Taille de l\'entreprise.',
            ])
            ->add('localisation', CountryType::class, [
                'required' => true,
                'label' => 'Pays',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'data' => 'MG',
                'help' => 'Localisation.',
            ])
            ->add('devise', EntityType::class, [
                'class' => Devise::class,
                'choice_label' => 'nom',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Devise de l\'entreprise.',
            ])
            ->add('siteWeb', TextType::class, [
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Site Web de l\'entreprise.',
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Contenu',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Description de l\'entreprise.',
                'attr' => [
                    'rows' => 6,
                    'class' => 'full-ckeditor-textarea'
                ]
            ])
            ->add('status', ChoiceType::class, [
                'choices' => EntrepriseProfile::getStatuses(),
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Statut de l\'entreprise.',
            ])
            ->add('file', VichImageType::class, [
                'label' => 'Logo de l\'entreprise',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'required' => false,
                'allow_delete' => true, 
                'download_uri' => true, 
                'image_uri' => true,    
            ])
            ->add('secteurs', EntityType::class, [
                'class' => Secteur::class,
                'choice_label' => 'nom',
                'autocomplete' => true,
                'multiple' => true,
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Secteurs d\'activités de l\'entreprise.',
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
