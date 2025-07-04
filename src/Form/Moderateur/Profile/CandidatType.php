<?php

namespace App\Form\Moderateur\Profile;

use App\Entity\Secteur;
use App\Entity\CandidateProfile;
use App\Entity\Candidate\Competences;
use App\Form\Candidat\TarifCandidatType;
use Symfony\Component\Form\AbstractType;
use App\Form\Candidat\AvailabilityEditType;
use App\Form\Profile\Candidat\Edit\SocialType;
use App\Form\Profile\Candidat\Edit\InfoUserType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File;
use App\EventSubscriber\ProvinceRegionSubscriber;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class CandidatType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'required' => true,
                'label' => 'Titre *',
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'Genre *',
                'choices' => CandidateProfile::getGenderLabels(),
                'expanded' => false,
                'multiple' => false,
                'required' => true,
            ])
            ->add('province', ChoiceType::class, [
                'choices' => [
                    'Antananarivo' => 'Antananarivo',
                    'Fianarantsoa' => 'Fianarantsoa',
                    'Toamasina' => 'Toamasina',
                    'Mahajanga' => 'Mahajanga',
                    'Toliara' => 'Toliara',
                    'Antsiranana' => 'Antsiranana',
                ],
                'required' => false,
                'placeholder' => 'Sélectionnez une province',
                'help' => 'Choisissez d’abord une province pour voir les régions disponibles.',
            ])
            ->addEventSubscriber(new ProvinceRegionSubscriber())
            ->add('candidat', InfoUserType::class, ['label' => false])
            ->add('resume', TextareaType::class, [
                'required' => false ,
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ]
            ])
            ->add('file', FileType::class, [
                'required' => false,
                'label' => 'app_identity_expert_step_one.avatar_desc',
                'constraints' => [
                    new File([
                        'maxSize' => '2048k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/jpg',
                            'image/bmp',
                        ],
                    ])
                ],
            ])
            ->add('localisation', CountryType::class, [
                'required' => true,
                'label' => 'Pays *',
            ])
            ->add('cv', FileType::class, [
                'label' => 'app_identity_expert.cv',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'custom-file-input'],
                'constraints' => [
                    new File([
                        'maxSize' => '4096k',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger un document PDF valide',
                    ])
                ],
            ])
            ->add('competences', EntityType::class, [
                'class' => Competences::class,
                'label' => 'Compétences',
                'choice_label' => 'nom',
                'autocomplete' => true,
                'multiple' => true,
                'required' => false,
            ])
            ->add('secteurs', EntityType::class, [
                'class' => Secteur::class,
                'label' => 'Secteur d\'expertise *',
                'choice_label' => 'nom',
                'autocomplete' => true,
                'multiple' => true,
                'required' => true,
            ])
            ->add('social', SocialType::class, ['label' => false])
            ->add('isPremium', CheckboxType::class, [
                'label' => 'Abonnement premium',
                'required' => false,
            ])
            // ->add('availability', ChoiceType::class, [
            //     'choices' => [
            //         'Immediatement' => 'immediate',
            //         'A partir du' => 'from-date',
            //         'Temps plein' => 'full-time',
            //         'Temps partiel' => 'part-time',
            //         'En poste' => 'not-available',
            //     ],
            //     'data' => 'immediate',
            //     'required' => false,
            //     'label' => false,
            //     'placeholder' => 'Disponibilité ...',
            // ])
            ->add('tarifCandidat', TarifCandidatType::class, [
                'required' => false,
                'label' => 'Prétention salariale',
            ])
            ->add('availability', AvailabilityEditType::class, [
                'required' => false,
                'label' => 'Disponibilité',
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
