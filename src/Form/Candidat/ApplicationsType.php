<?php

namespace App\Form\Candidat;

use App\Entity\Candidate\Applications;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplicationsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ->add('cvLink', null, [
            //     'label' =>  'Mettre à jour mon CV',
            //     'attr' => ['id' => 'cv-link-field']
            // ])
            ->add('lettreMotivation', TextareaType::class, [
                'label' =>  false,
                'required' => false,
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ]
            ])
            // ->add('pretentionSalariale')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Applications::class,
        ]);
    }
}
