<?php

namespace App\Form\Autocomplete;

use App\Entity\CandidateProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField]
class CandidateAutocompleteField extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => CandidateProfile::class,
            'placeholder' => 'Séléctionner un candidat',
            'label' => 'Candidat',
            'choice_label' => function(?CandidateProfile $candidateProfile) {
                return $candidateProfile ? $candidateProfile->getMatricule() : '';
            },

            // choose which fields to use in the search
            // if not passed, *all* fields are used
            'searchable_fields' => ['id', 'titre'],

            // 'security' => 'ROLE_SOMETHING',
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
