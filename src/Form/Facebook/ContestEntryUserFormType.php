<?php

namespace App\Form\Facebook;

use App\Entity\Facebook\ContestEntry;
use Symfony\Component\Form\AbstractType;
use App\Form\Facebook\RegisterContestType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContestEntryUserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', RegisterContestType::class, [
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
