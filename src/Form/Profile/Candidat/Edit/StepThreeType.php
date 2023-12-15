<?php

namespace App\Form\Profile\Candidat\Edit;

use App\Entity\CandidateProfile;
use Symfony\Component\Form\AbstractType;
use App\Form\Profile\Candidat\SocialType;
use App\Form\Profile\Candidat\LangagesType;
use App\Form\Profile\Candidat\CompetencesType;
use App\Form\Profile\Candidat\ExperiencesType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class StepThreeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('competences', CollectionType::class, [
            'entry_type' => CompetencesType::class,
            'entry_options' => ['label' => false],
            'allow_add' => true,
            'by_reference' => false,
        ])
        ->add('experiences', CollectionType::class, [
            'entry_type' => ExperiencesType::class,
            'entry_options' => ['label' => true],
            'allow_add' => true,
            'by_reference' => false,
        ])
        ->add('langages', CollectionType::class, [
            'entry_type' => LangagesType::class,
            'entry_options' => [
                'label' => false,
            ],
            'allow_add' => true,
            'by_reference' => false,
        ])
        ->add('cv', FileType::class, [
            'label' => 'app_identity_expert.cv',
            'label_attr' => ['class' => 'col-sm-4 text-center col-form-label'],
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
