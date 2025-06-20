<?php

namespace App\WhiteLabel\Form\Client1\Profile\Candidat\Edit;

use App\WhiteLabel\Entity\Client1\Secteur;
use App\WhiteLabel\Entity\Client1\CandidateProfile;
use App\WhiteLabel\Form\Client1\Candidat\TarifCandidatType;
use Symfony\Component\Form\AbstractType;
use App\WhiteLabel\Form\Client1\Profile\Candidat\SocialType;
use App\WhiteLabel\Form\Client1\Profile\Candidat\LangagesType;
use App\WhiteLabel\Form\Client1\Profile\Candidat\CompetencesType;
use App\WhiteLabel\Form\Client1\Profile\Candidat\ExperiencesType;
use App\WhiteLabel\Form\Client1\Profile\Candidat\Edit\InfoUserType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class EditCandidateProfile extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('file', FileType::class, [
                'required' => false,
                'label' => 'app_identity_expert_step_one.avatar',
                'attr' => ['class' => 'd-none'],
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
            ->add('candidat', InfoUserType::class, ['label' => false])
            ->add('localisation', CountryType::class, [
                'required' => true,
                'label' => 'Pays',
                'attr' => ['class' => 'd-none'],
            ])
            ->add('birthday', DateType::class, [
                'label' => 'Votre anniversaire',
                'years' => range(1970, 2010),
                'attr' => ['class' => 'rounded-pill'] 
            ])
            ->add('resume', TextareaType::class, [
                'label' => 'app_identity_expert.aspiration',
                'required' => false,
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ]
            ])
            ->add('titre', TextType::class, [
                'required' => false,
                'label' => 'app_identity_expert.name',
            ])
            ->add('tarifCandidat', TarifCandidatType::class, [
                'required' => false,
                'label' => 'Prétention salariale',
            ])
            ->add('social', SocialType::class, ['label' => false])
            ->add('secteurs', EntityType::class, [
                'class' => Secteur::class,
                'label' => 'app_identity_company.sector_multiple',
                'choice_label' => 'nom',
                'autocomplete' => true,
                'multiple' => true,
                'required' => true,
            ])
            ->add('competences', CollectionType::class, [
                'entry_type' => CompetencesType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'entry_options' => ['label' => false],
                'attr' => [
                    'data-controller' => 'form-collection',
                    'data-form-collection-add-label-value' => 'Ajouter une compétence',
                    'data-form-collection-delete-label-value' => 'Supprimer'
                ]
            ])
            ->add('experiences', CollectionType::class, [
                'entry_type' => ExperiencesType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'entry_options' => ['label' => false],
                'attr' => [
                    'data-controller' => 'form-collection',
                    'data-form-collection-add-label-value' => 'Ajouter une expérience',
                    'data-form-collection-delete-label-value' => 'Supprimer'
                ]
            ])
            ->add('langages', CollectionType::class, [
                'entry_type' => LangagesType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'entry_options' => ['label' => false],
                'attr' => [
                    'data-controller' => 'form-collection',
                    'data-form-collection-add-label-value' => 'Ajouter une langue',
                    'data-form-collection-delete-label-value' => 'Supprimer',
                ]
            ])
            ->add('cv', FileType::class, [
                'label' => 'Ajouter un CV',
                'label_attr' => ['class' => 'col-form-label'],
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
                        'mimeTypesMessage' => 'Veuillez télécharger un document PDF valide.',
                    ])
                ],
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
