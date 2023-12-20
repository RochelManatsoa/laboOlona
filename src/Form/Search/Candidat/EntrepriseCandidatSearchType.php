<?php

namespace App\Form\Search\Candidat;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class EntrepriseCandidatSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'required' => false,
                'label' => false,
                'attr' => [
                    'placeholder' => 'Nom, prénom ou email ...',
                ]
            ])
            ->add('titre', TextType::class, [
                'required' => false,
                'label' => false,
                'attr' => [
                    'placeholder' => 'Poste occupé ...',
                ]
            ])
            ->add('competences', TextType::class, [
                'required' => false,
                'label' => false,
                'attr' => [
                    'placeholder' => 'Compétences ...',
                ]
            ])
            ->add('langues', TextType::class, [
                'required' => false,
                'label' => false,
                'attr' => [
                    'placeholder' => 'Langues ...',
                ]
            ])
            ->add('availability', ChoiceType::class, [
                'choices' => [
                    'Immediatement' => 'immediate',
                    'A partir du' => 'from-date',
                    'Temps plein' => 'full-time',
                    'Temps partiel' => 'part-time',
                    'En poste' => 'not-available',
                ],
                'required' => false,
                'label' => false,
                'placeholder' => 'Disponibilité ...',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
            'types_contrat' => [],
        ]);
    }
}