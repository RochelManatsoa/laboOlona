<?php

namespace App\Form\Profile\Candidat;

use App\Entity\CandidateProfile;
use Symfony\Component\Form\AbstractType;
use App\Form\Profile\Candidat\SocialType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StepThreeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('social', SocialType::class, ['label' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CandidateProfile::class,
        ]);
    }
}
