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
                'attr' => ['class' => 'form-control experience-field', 'style' => 'width: 100%']
            ])
            ->add('entreprise', TextType::class, [
                'label' => 'app_identity_expert_step_two.experience.company',
                'attr' => ['class' => 'form-control experience-field', 'style' => 'width: 100%']
            ])
            ->add('enPoste', CheckboxType::class, [
                'label' => 'app_identity_expert_step_two.experience.currently',
                'required' => false,
                'attr' => ['data-form-collection-target' => 'currentlyCheckbox', 'class' => 'form-check-input']
            ])
            ->add('dateDebut', DateType::class, [
                'label' => 'Date de début',
                'attr' => [
                    'class' => 'form-control experience-field', 
                    'style' => 'width: 50%'
                ],
                'widget' => 'single_text',  
                'format' => 'yyyy-MM-dd', 
            ])
            ->add('dateFin', DateType::class, [
                'label' => 'Date fin',
                'attr' => [
                    'class' => 'form-control experience-field',
                    'data-form-collection-target' => 'endDate',
                    'style' => 'width: 50%'
                ],
                'widget' => 'single_text',  
                'format' => 'yyyy-MM-dd', 
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'app_identity_expert_step_two.experience.description',
                'required' => false,
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea form-control',
                    'style' => 'width: 100%'
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
