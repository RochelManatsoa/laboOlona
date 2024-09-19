<?php

namespace App\Form\Autocomplete;

use App\Entity\Candidate\Competences;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField]
class CompetencesAutocompleteField extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Competences::class,
            'attr' => [
                'data-controller' => 'competences-add-autocomplete',
            ],
            'choice_label' => 'nom',
            'placeholder' => 'Séléctionner ou ajouter une compéte',
            'multiple' => true,
            'allow_options_create' => true,
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}