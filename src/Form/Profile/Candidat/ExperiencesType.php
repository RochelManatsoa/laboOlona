<?php

namespace App\Form\Profile\Candidat;

use App\Entity\Candidate\Experiences;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ExperiencesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('nom', TextType::class, [
            'label' => 'app_identity_expert_step_two.experience.title',
        ])
        ->add('entreprise', TextType::class, [
            'label' => 'app_identity_expert_step_two.experience.company',
        ])
        ->add('enPoste', CheckboxType::class, [
            'label' => 'app_identity_expert_step_two.experience.currently',
            'required' => false,
        ])
        ->add('dateDebut', DateType::class,  [
            'label' => 'app_identity_expert_step_two.experience.startDate',
            'years' => range(1950, (new \DateTime('now'))->format("Y")),
            'attr' => ['class' => 'rounded-pill'] 
        ])
        ->add('dateFin', DateType::class,  [
            'label' => 'app_identity_expert_step_two.experience.endDate',
            'years' => range(1950, 2100),
            'attr' => ['class' => 'rounded-pill'] ,
            'data' => new \DateTime('now'),
            'required' => false,
        ])
        ->add('description', TextareaType::class, [
            'label' => 'app_identity_expert_step_two.experience.description',
            'required' => false,
            'attr' => [
                'rows' => 6,
                'class' => 'ckeditor-textarea'
            ]
        ])
        ->add('submit', SubmitType::class, [
            'label' => 'app_identity_expert_step_two.experience.submit',
            'attr' => [
                'class' => 'btn btn-dark rounded-pill'
            ]
        ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Experiences::class,
        ]);
    }
}
