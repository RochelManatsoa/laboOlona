<?php

namespace App\Form\Moderateur;

use App\Entity\Secteur;
use App\Entity\CandidateProfile;
use App\Form\Profile\Candidat\CompetencesType;
use Symfony\Component\Form\AbstractType;
use App\Form\Profile\Candidat\SocialType;
use App\Form\Profile\Candidat\Edit\InfoUserType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class CandidatType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('resume', TextareaType::class, [
                'label' => 'Biographie',
                'required' => false,
                'attr' => [
                    'rows' => 8
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
            ->add('titre', TextType::class, [
                'required' => true,
                'label' => 'Titre *',
            ])
            ->add('candidat', InfoUserType::class, ['label' => false])
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
            ->add('secteurs', EntityType::class, [
                'class' => Secteur::class,
                'label' => 'Secteur d\'expertise *',
                'choice_label' => 'nom',
                'autocomplete' => true,
                'multiple' => true,
                'required' => true,
            ])
            ->add('social', SocialType::class, ['label' => false])
            ->add('competences', CollectionType::class, [
                'entry_type' => CompetencesType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
            ])
            // ->add('birthday')
            // ->add('availability')
            // ->add('isValid')
            // ->add('status')
            // ->add('uid')
            // ->add('emailSent')
            // ->add('createdAt')
            // ->add('updatedAt')
            // ->add('social')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CandidateProfile::class,
        ]);
    }
}
