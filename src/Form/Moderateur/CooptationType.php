<?php

namespace App\Form\Moderateur;

use App\Entity\Referrer\Referral;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\UuidType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CooptationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('annonce')
            ->add('referredEmail')
            ->add('referralCode', UuidType::class, [
                'attr' => [
                    'readonly' => true, 
                ]
            ])
            ->add('status', ChoiceType::class, [
                'choices' => Referral::getStatuses()
            ])
            ->add('rewards', MoneyType::class, [])
            ->add('step')
            ->add('description')
            // ->add('createdAt')
            // ->add('referredBy')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Referral::class,
        ]);
    }
}
