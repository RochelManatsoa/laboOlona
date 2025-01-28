<?php

namespace App\Form\Facebook;

use App\Entity\Facebook\ContestEntry;
use Symfony\Component\Form\AbstractType;
use App\Form\Facebook\CandidatContestType;
use App\Form\Facebook\RegisterContestType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContestEntryFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', RegisterContestType::class, [])
            ->add('candidateProfile', CandidatContestType::class, [])
            ->add('userId', HiddenType::class, [
                'mapped' => false,
                'required' => false
            ])
            ->add('profileId', HiddenType::class, [
                'mapped' => false,
                'required' => false
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
