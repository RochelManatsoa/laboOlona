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
            // ->add('file', FileType::class, [
            //     'required' => false,
            //     'label' => 'Votre logo',
            //     'attr' => ['class' => 'd-none'],
            //     'label_attr' => [
            //         'class' => 'fw-bold fs-5' 
            //     ],
            //     'help' => 'Uploader votre logo de l\'entreprise.',
            //     'constraints' => [
            //         new File([
            //             'maxSize' => '2048k',
            //             'mimeTypes' => [
            //                 'image/jpeg',
            //                 'image/png',
            //                 'image/jpg',
            //                 'image/bmp',
            //             ],
            //         ])
            //     ],
            // ])
            // ->add('devise', EntityType::class, [
            //     'class' => Devise::class,
            //     'label' => 'Séléctionner votre devise',
            //     'label_attr' => [
            //         'class' => 'fw-bold fs-5' 
            //     ],
            //     'help' => 'Choisissez la devise que vous utilisez pour les transactions.',
            // ])
            // ->add('siteWeb', TextType::class, [
            //     'label' => 'Site Internet (facultatif)',
            //     'label_attr' => [
            //         'class' => 'fw-bold fs-5' 
            //     ],
            //     'help' => 'Entrez l\'URL complète de votre site web (ex: https://www.monsite.com).',
            // ])
            // ->add('taille', ChoiceType::class, [
            //     'choices' => EntrepriseProfile::CHOICE_SIZE,
            //     'label_attr' => [
            //         'class' => 'fw-bold fs-5' 
            //     ],
            //     'help' => 'Sélectionnez la taille approximative de votre entreprise.',
            // ])
            ->add('nom', TextType::class, [
                'label' => 'Raison sociale (*)',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Indiquez la raison sociale complète de votre entreprise.',
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Description (*)',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Décrivez brièvement votre entreprise et ses activités.',
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
            ->add('localisation', CountryType::class, [
                'label' => 'Pays (*)',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Sélectionnez le pays où est située votre entreprise.',
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
