<?php

namespace App\Form\V2;

use App\Entity\Secteur;
use App\Entity\Finance\Devise;
use App\Entity\EntrepriseProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class RecruiterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('secteurs', EntityType::class, [
                'required' => false,
                'class' => Secteur::class,
                'label' => 'Secteurs d\'activité *',
                'choice_label' => 'nom',
                'autocomplete' => true,
                'multiple' => true,
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Sélectionnez les secteurs d\'activité de votre entreprise (obligatoire).',
                'constraints' => new Sequentially([
                    new NotBlank(message:'Les secteurs de l\'entreprise sont obligatoires.'),
                ]),
            ])
            ->add('nom', null, [
                'required' => false,
                'label' => 'Nom de votre entreprise *',
                'help' => 'Entrez le nom officiel de votre entreprise (obligatoire).',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'constraints' => new Sequentially([
                    new NotBlank(message:'Le nom de l\'entreprise est obligatoire.'),
                ]),
            ])
            ->add('taille', ChoiceType::class, [
                'label' => 'Taille de votre entreprise',
                'choices' => EntrepriseProfile::CHOICE_SIZE,
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Sélectionnez la taille approximative de votre entreprise.',
            ])
            ->add('localisation', CountryType::class, [
                'required' => false,
                'label' => 'Pays de résidence *',
                'placeholder' => 'Sélectionner votre pays', 
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Choisissez le pays où est basée votre entreprise (obligatoire).',
                'constraints' => new Sequentially([
                    new NotBlank(message:'Le pays de l\'entreprise est obligatoire.'),
                ]),
            ])
            ->add('siteWeb', null, [
                'required' => false,
                'label' => 'Site web',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Entrez l\'URL de votre site web (optionnel).',
            ])
            ->add('devise', EntityType::class, [
                'label' => 'Votre devise',
                'class' => Devise::class,
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Sélectionnez la devise utilisée par votre entreprise.',
            ])
            ->add('description', TextareaType::class, [
                'required' => false, 
                'label' => 'Description de votre entreprise *',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ],
                'help' => 'Décrivez brièvement l\'activité et les objectifs de votre entreprise (obligatoire).',
                'constraints' => new Sequentially([
                    new NotBlank(message:'La description de l\'entreprise est obligatoire.'),
                ]),
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
