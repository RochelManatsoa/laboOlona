<?php

namespace App\Form;

use App\Entity\Entreprise\JobListing;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class JobListingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Ouvert' => 'OPEN',
                    'Fermé' => 'CLOSED',
                    'Pourvu' => 'FILLED',
                ],
            ])
            // Ajoutez ici d'autres champs si nécessaire
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JobListing::class,
        ]);
    }
}