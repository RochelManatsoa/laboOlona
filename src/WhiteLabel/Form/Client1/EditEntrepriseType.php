<?php

namespace App\WhiteLabel\Form\Client1;

use App\WhiteLabel\Entity\Client1\Secteur;
use App\WhiteLabel\Form\Client1\ContactType;
use App\WhiteLabel\Entity\Client1\Finance\Devise;
use App\WhiteLabel\Entity\Client1\EntrepriseProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class EditEntrepriseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom (*)',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Nom du département recruteur.',
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'A propos',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Décrivez brièvement votre activités.',
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ]
            ])
            ->add('secteurs', EntityType::class, [
                    'class' => Secteur::class,
                    'choice_label' => 'nom',
                    'label_attr' => [
                        'class' => 'fw-bold fs-5' 
                    ],
                    'label' => 'Secteur(s) d\'expertise (*)',
                    'autocomplete' => true,
                    'multiple' => true,
                    'help' => 'Sélectionnez un ou plusieurs secteurs dans lesquels votre entreprise est active.',
            ])
            ->add('entreprise', ContactType::class, [
                'label' => false,
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
