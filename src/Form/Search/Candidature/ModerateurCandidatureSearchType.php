<?php

namespace App\Form\Search\Candidature;

use App\Entity\Candidate\Applications;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ModerateurCandidatureSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'required' => false,
                'label' => false,
                'attr' => [
                    'placeholder' => 'Titre de l\'annonce ...',
                ]
            ])
            ->add('entreprise', TextType::class, [
                'required' => false,
                'label' => false,
                'attr' => [
                    'placeholder' => 'Entreprise ...',
                ]
            ])
            ->add('candidat', TextType::class, [
                'required' => false,
                'label' => false,
                'attr' => [
                    'placeholder' => 'Candidat ...',
                ]
            ])
            ->add('status', ChoiceType::class, [
                'choices' => Applications::getStatuses(),
                'required' => false,
                'label' => false,
                'placeholder' => 'Status ...',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}