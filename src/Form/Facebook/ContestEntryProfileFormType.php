<?php

namespace App\Form\Facebook;

use App\Entity\Facebook\ContestEntry;
use Symfony\Component\Form\AbstractType;
use App\Form\Facebook\CandidatContestType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContestEntryProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('candidateProfile', CandidatContestType::class, [
                'label' => false
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
