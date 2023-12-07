<?php

namespace App\Form\Profile\Candidat\Edit;

use App\Entity\Secteur;
use App\Entity\CandidateProfile;
use Symfony\Component\Form\AbstractType;
use App\Form\Profile\Candidat\Edit\SocialType;
use App\Form\Profile\Candidat\ExperiencesType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class StepTwoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'required' => false,
                'label' => 'app_identity_expert.name',
            ])
            ->add('resume', TextareaType::class, [
                'label' => 'app_identity_expert.aspiration',
                'required' => false,
                'attr' => [
                    'rows' => 8
                ]
            ])
            ->add('social', SocialType::class, ['label' => false])
            // ->add('cv', FileType::class, [
            //     'label' => 'app_identity_expert.cv',
            //     'mapped' => false,
            //     'required' => false,
            //     'constraints' => [
            //         new File([
            //             'maxSize' => '4096k',
            //             'mimeTypes' => [
            //                 'application/pdf',
            //                 'application/x-pdf',
            //             ],
            //             'mimeTypesMessage' => 'Please upload a valid PDF document',
            //         ])
            //     ],
            // ])
            // ->add('competences', CollectionType::class, [
            //     'entry_type' => CompetencesType::class,
            //     'entry_options' => ['label' => false],
            //     'allow_add' => true,
            //     'by_reference' => false,
            // ])
            // ->add('experiences', CollectionType::class, [
            //     'entry_type' => ExperiencesType::class,
            //     'entry_options' => ['label' => true],
            //     'allow_add' => true,
            //     'by_reference' => false,
            // ])
            // ->add('langages', CollectionType::class, [
            //     'entry_type' => LangagesType::class,
            //     'entry_options' => ['label' => true],
            //     'allow_add' => true,
            //     'by_reference' => false,
            // ])
            ->add('secteurs', EntityType::class, [
                'class' => Secteur::class,
                'label' => 'app_identity_company.sector_multiple',
                'choice_label' => 'nom',
                'expanded' => true,
                'multiple' => true,
                'required' => true,
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
