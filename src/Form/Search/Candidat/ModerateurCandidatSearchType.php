<?php

namespace App\Form\Search\Candidat;

use App\Manager\ModerateurManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ModerateurCandidatSearchType extends AbstractType
{

    public function __construct(
        private ModerateurManager $moderateurManager,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'required' => false,
                'label' => false,
                'attr' => [
                    'placeholder' => 'Nom ou prénoms ...',
                ]
            ])
            ->add('titre', TextType::class, [
                'required' => false,
                'label' => false,
                'attr' => [
                    'placeholder' => 'Nom de poste ...',
                ]
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Valide' => 'VALID',
                    'En attente' => 'PENDING',
                    'Mis en avant' => 'FEATURED',
                    'Vivier' => 'RESERVED',
                ],
                'required' => false,
                'label' => false,
                'placeholder' => 'Statut ...',
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}