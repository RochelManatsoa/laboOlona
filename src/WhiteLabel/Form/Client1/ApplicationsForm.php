<?php

namespace App\WhiteLabel\Form\Client1;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\WhiteLabel\Entity\Client1\CandidateProfile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\WhiteLabel\Entity\Client1\Entreprise\JobListing;
use App\WhiteLabel\Entity\Client1\Candidate\Applications;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ApplicationsForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('candidat', EntityType::class, [
                'class' => CandidateProfile::class,
                'choice_label' => 'id',
            ])
            ->add('annonce', EntityType::class, [
                'class' => JobListing::class,
                'choice_label' => 'titre',
            ])
            ->add('cvLink')
            // ->add('dateCandidature')
            ->add('status')
            ->add('pretentionSalariale')
            ->add('lettreMotivation', TextareaType::class, [
                'required' => false ,
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Applications::class,
        ]);
    }
}
