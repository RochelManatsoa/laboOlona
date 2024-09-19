<?php

namespace App\Form\Profile\Referrer;

use App\Form\InfoUserType;
use App\Entity\ReferrerProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StepOneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('referrer', InfoUserType::class, ['label' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReferrerProfile::class,
        ]);
    }
}
