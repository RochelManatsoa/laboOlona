<?php

namespace App\Form\Search\Annonce;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class CandidatAnnonceSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'required' => false,
                'label' => false,
                'attr' => [
                    'placeholder' => 'Titre du poste',
                ]
            ])
            ->add('lieu', TextType::class, [
                'required' => false,
                'label' => false,
                'attr' => [
                    'placeholder' => 'Lieu',
                ]
            ])
            ->add('competences', TextType::class, [
                'required' => false,
                'label' => false,
                'attr' => [
                    'placeholder' => 'Compétences',
                ]
            ])
            ->add('typeContrat', ChoiceType::class, [
                'choices' => $options['types_contrat'],
                'choice_label' => function($typeContrat) {
                    return $typeContrat->getNom(); // Assurez-vous que getNom() est une méthode valide dans votre entité TypeContrat
                },
                'label' => false,
                'required' => false,
                'placeholder' => 'Contrat',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
            'types_contrat' => [],
        ]);
    }
}