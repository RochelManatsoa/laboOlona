<?php

namespace App\Form\Facebook;

use App\Entity\User;
use App\Entity\CandidateProfile;
use App\Entity\Facebook\Contest;
use App\Entity\Facebook\ContestEntry;
use Symfony\Component\Form\AbstractType;
use App\Form\Autocomplete\UserAutocompleteField;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContestEntryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contest', EntityType::class, [
                'class' => Contest::class,
                'label' => 'Concours',
                'choice_label' => 'name',
            ])
            ->add('user', UserAutocompleteField::class, [])
            ->add('cvSumbitted')
            ->add('status', ChoiceType::class, [
                'choices' => ContestEntry::getStatuses(),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContestEntry::class,
        ]);
    }
}
