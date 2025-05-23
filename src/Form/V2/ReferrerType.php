<?php

namespace App\Form\V2;

use App\Entity\ReferrerProfile;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReferrerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('raisonSocial')
            ->add('nif')
            ->add('statutJuridique')
            ->add('creation')
            ->add('adressePro')
            ->add('telephonePro')
            ->add('emailPro')
            ->add('customId')
            ->add('description')
            ->add('totalRewards')
            ->add('pendingRewards')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReferrerProfile::class,
        ]);
    }
}
