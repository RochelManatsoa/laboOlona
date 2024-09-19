<?php

namespace App\Form\Profile\Candidat\Edit;

use App\Entity\Secteur;
use App\Entity\CandidateProfile;
use App\Form\Candidat\TarifCandidatType;
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
            ->add('tarifCandidat', TarifCandidatType::class, [
                'required' => false,
                'label' => 'Prétention salariale',
            ])
            ->add('resume', TextareaType::class, [
                'label' => 'app_identity_expert.aspiration',
                'required' => false,
                'attr' => [
                    'rows' => 8
                ]
            ])
            ->add('social', SocialType::class, ['label' => false])
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
