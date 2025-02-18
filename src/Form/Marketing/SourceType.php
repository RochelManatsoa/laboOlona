<?php

namespace App\Form\Marketing;

use App\Entity\Marketing\Source;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class SourceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => true,
                'label' => 'Nom de la source',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Ex: "Candidature", "Formulaire de contact", "Entreprises partenaires" ...',
            ])
            ->add('nameSpace', TextType::class, [
                'required' => true,
                'label' => 'Nom de la classe',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Ne pas modifier, il est automatiquement généré.',
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Description',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'A propos de cette source marketing.',
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
            'data_class' => Source::class,
        ]);
    }
}
